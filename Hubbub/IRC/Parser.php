<?php
/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2015, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 *
 * @todo Would be useful to have an auto bug submit (after approval/manually enabled) when the parser fails, i.e. receives unexpected data
 * @todo This function makes use of trigger_warning() which should be changed to use the internal logger class
 */

/*
 * The following large collection of callbacks (all private methods) are based on the following files:
 * https://www.alien.net.au/irc/irc2numerics.html
 * https://www.alien.net.au/irc/irc2numerics.def
 *
 * Original files are Copyright (c) 2001,2002,2003,2004 Simon Butcher <pickle@alien.net.au>
 * We thank him profusely for them.  They are public domain.
 *
 * All the descriptions, format data, etc, was taken from this file and used to generate a large template, and then modified and extended from there.
 */

namespace Hubbub\IRC;

use StdClass;

trait Parser {
    use Numerics;

    /**
     * @param string $c A raw, unformatted IRC protocol line.
     *
     * @return array A parsed, structured array containing formatted IRC data.
     *
     * This is the main IRC protocol processing method.  It returns a pretty array with the following indexes:
     * @todo finalize docs when done
     */
    public function parseIrcCommand($c) {
        $parsed = new StdClass();
        $parsed->protocol = 'irc';
        $parsed->data = $c;

        $c = trim($c); // May already have been trimmed by the time it gets here, but it can't hurt

        // It seems all server-to-client messages should be prefixed with ':'
        // However this function could be used to parse outgoing protocol data as well
        if(substr($c, 0, 1) == ':') {
            list($sender, $cmd, $arg) = explode(' ', $c, 3);
        } else {
            // Should only be for client-to-server
            list($cmd, $arg) = explode(' ', $c, 2);
            $sender = null;
        }

        $parsed->cmdData = $cmd;
        $parsed->argData = $arg;
        $parsed->from = $this->parseIrcHostmask($sender);

        // Turn numeric commands into their string equivilants.  Numeric commands will still be accessible in ->cmdData
        if(is_numeric($cmd)) { // @todo while no irc commands meet this, keep in mind this will return true for e.x. '+01' and '0x0F'
            $newCommand = $this->numericToCommand($cmd);
            if($newCommand === false) {
                trigger_error("I don't have a definition for numeric command $cmd\n", E_USER_WARNING);
            } else {
                $cmd = $newCommand;
            }
        }
        $parsed->cmd = strtolower($cmd);

        // Now gracefully parse arguments taking into account the trailingArg as defined
        // in the RFC
        $hasTrailing = false;
        if(substr($arg, 0, 1) == ':') {
            $args = [substr($arg, 1)];
            $hasTrailing = true;
        } else {
            $trailingPos = strpos($arg, ' :');

            if($trailingPos !== false) {
                $endPos = $trailingPos;
            } else {
                $endPos = strlen($arg);
            }

            $args = explode(' ', substr($arg, 0, $endPos));

            if(strlen($arg) > $endPos) { // if there is a trailing bit...
                $args[] = substr($arg, $endPos + 2);
                $hasTrailing = true;
            }
        }

        $parsed->args = $args;
        $parsed->argsHasTrailing = $hasTrailing;

        // Call it's parser callback if available.
        if(method_exists($this, 'parse_' . $parsed->cmd)) {
            $parsed = $this->{'parse_' . $parsed->cmd}($parsed);
        } else {
            trigger_error("I don't have a parser method for the previous command!", E_USER_WARNING);
        }

        return $parsed;
    }

    /**
     * Parses a RFC compatible hostmask into a StdClass with the following indices: nick, user, host, ident, and possibly, 'server'.
     *
     * @param string $mask The hostmask to parse.
     *
     * @return StdClass
     */
    public function parseIrcHostmask($mask) {
        // RFC: <prefix>   ::= <servername> | <nick> [ '!' <user> ] [ '@' <host> ]
        $output = new StdClass();
        $output->raw = $mask;

        // Drop the : if we still have it
        if(substr($mask, 0, 1) == ':') {
            $mask = substr($mask, 1);
        }

        if(strpos($mask, '!') !== false) {
            list($nick, $mask) = explode('!', $mask);
        } else {
            $nick = null;
        }

        if(strpos($mask, '@') !== false) {
            list($user, $mask) = explode('@', $mask);
            if(substr($user, 0, 1) == '~') {
                $ident = 0;
                $user = substr($user, 1); // Drop the ~
            } else {
                $ident = 1;
            }
        } else { // @todo Technically this should be able to properly parse simply nick!user with no @host part but it does not
            $user = null;
            $ident = null;
        }
        // TODO possibly use a regex (or something better..)
        // to determine if it's a nick OR host.  To my knowledge,
        // it's far more likely to be a host at this point, but according
        // to the prototype, it /could/ be a nick

        if($nick === null) {
            $output->server = $mask;
            $mask = null;
        } else {
            $output->server = false;
        }

        $output->nick = $nick;
        $output->user = $user;
        $output->host = $mask;
        $output->ident = $ident;

        return $output;
    }

    /*
     * Non-Numeric parsers
     */

    /**
     * Parsed PRIVMSG's and also NOTICES can be parsed here as well.
     *
     * @todo Handle identify-msg extended capability
     *
     * @param $line
     *
     * @return mixed
     */

    /**
     * parse_privmsg() can parse PRIVMSG's and NOTICE's, and also CTCP messages which are carried by PRIVMSG's.  In the case of CTCP, an additional property
     * is added called 'ctcp' and the command is changed to 'ctcp'
     *
     * @param StdClass $line A partially pre-parsed IRC protocol line
     * @param bool     $checkCtcp Whether or not to attempt to parse it as a CTCP message
     *
     * @return mixed
     */
    private function parse_privmsg($line, $checkCtcp = true) {
        if(!empty($line->from->server)) {
            $line->type = 'server-message';
        } else {
            $line->type = 'message';
        }

        $message = new StdClass();
        $message->to = $this->parseIrcHostmask($line->args[0]);
        $message->msg = $line->args[1];
        $line->{$line->cmd} = $message;

        // Check for ctcp marker -- only privmsg's
        if($checkCtcp) {
            if(substr($message->msg, 0, 1) === chr(1) && substr($message->msg, -1) === chr(1)) { // it is a ctcp
                $ctcpData = substr($message->msg, 1, -1); // Strip the 0x01 off both ends
                if(strpos($ctcpData, ' ') !== false) {
                    list($ctcpCmd, $ctcpArg) = explode(' ', $ctcpData);
                } else {
                    $ctcpCmd = $ctcpData;
                    $ctcpArg = null;
                }

                $line->cmd = 'ctcp';
                $line->type = 'meta';
                $line->ctcp = $ctcpData;
            }
        }

        return $line;
    }

    /**
     * Parses NOTICEs using the PRIVMSG parser.  See parse_privmsg()
     *
     * @return mixed
     */
    private function parse_notice($line) {
        return $this->parse_privmsg($line, false); // False will turn off CTCP checking.
    }

    /**
     * @param StdClass $line
     *
     * @return mixed
     */
    /*private function parse_cap($line) {
        return $line;
    }*/

    private function parse_join(StdClass $cmd) {
        $cmd->join = [
            'channels' => $cmd->args
        ];

        return $cmd;
    }

    /*
     * Section 000-199, local server to client connections
     */

    /**
     * 001 RPL_WELCOME. Originated from RFC2812.
     * The first message sent after client registration. The text used varies widely
     * :Welcome to the Internet Relay Network <nick>!<user>@<host>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_welcome(StdClass $line) {
        return $line;
    }

    /**
     * 002 RPL_YOURHOST. Originated from RFC2812.
     * Part of the post-registration greeting. Text varies widely
     * :Your host is <servername>, running version <version>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_yourhost(StdClass $line) {

        if(preg_match("/Your host is (.*), running version (.*)/i", $line->args[1], $m)) {
            $yourHost = $m[1];
            $ircdVersion = $m[2];
        } else {
            trigger_error("Could not parse RPL_YOURHOST correctly in Parser", E_USER_WARNING);
            $yourHost = null;
            $ircdVersion = null;
        }

        $line->{$line->cmd} = [
            'your-host'    => $yourHost,
            'ircd-version' => $ircdVersion
        ];

        return $line;
    }

    /**
     * 003 RPL_CREATED. Originated from RFC2812.
     * Part of the post-registration greeting. Text varies widely
     * :This server was created <date>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_created(StdClass $line) {
        if(preg_match("/was created (.*)/i", $line->args[1], $m)) {
            $serverCreated = $m[1];

            try {
                $serverCreatedDate = new \DateTime(str_replace(' at ', ' ', $serverCreated));
            } catch (\Exception $e) {
                trigger_warning("Could not parse RPL_CREATED's date correctly in Parser: " . $e->getMessage(), E_USER_WARNING);
                $serverCreatedDate = null;
            }
        } else {
            trigger_warning("Could not parse RPL_CREATED correctly in Parser", E_USER_WARNING);
            $serverCreated = null;
            $serverCreatedDate = null;
        }

        $line->{$line->cmd} = [
            'text'      => $serverCreated,
            'dateTime'  => $serverCreatedDate,
            'timestamp' => $serverCreatedDate->getTimestamp(),
        ];

        return $line;
    }

    /**
     * 004 RPL_MYINFO. Originated from RFC2812.
     * Part of the post-registration greeting
     * RFC2812 Format:
     * <server_name> <version> <user_modes> <chan_modes>
     * KineIRCd Extended Format (Same as RFC2812 however with additional fields to avoid additional 005 burden)
     * <server_name> <version> <user_modes> <chan_modes> <channel_modes_with_params> <user_modes_with_params> <server_modes> <server_modes_with_params>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_myinfo(StdClass $line) {

        $myInfo = [];
        $explodedIndexes = [
            'server-name', 'ircd-version', 'user-modes', 'chan-modes', 'chan-modes-parms', 'user-mode-parms', 'server-modes', 'server-modes-parms'
        ];

        foreach ($explodedIndexes as $index => $key) {
            if(isset($line->args[$index + 1])) {
                $myInfo[$key] = $line->args[$index + 1];
            }
        }

        $line->{$line->cmd} = $myInfo;

        return $line;
    }

    /**
     * 005 RPL_ISUPPORT.
     *
     * @link More Info http://www.irc.org/tech_docs/005.html
     * Also known as RPL_PROTOCTL (Bahamut, Unreal, Ultimate).
     * Can also be interpreted as RPL_BOUNCE in it's deprecated form.  Parser can appropriatley detect and convert into a RPL_BOUNCE if necessary
     * Deprecated RPL_BOUNCE:
     * See also command 010.
     * Sent by the server to a user to suggest an alternative server, sometimes used when the connection is refused because the server is already full. Also known as RPL_SLINE (AustHex), and RPL_REDIR
     * :Try server <server_name>, port <port_number>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_isupport(StdClass $line) {
        // @todo Needs testing.  Never tested against an actual production ircd.
        if(preg_match('/Try (.*), port (.*)/i', $arg, $m)) {
            $line->cmd = 'rpl_bounce';
            $line->cmdTranslated = true;
            $line->{$this->cmd} = [
                'server' => $m[1],
                'port'   => $m[2]
            ];

            return $line;
        } else { // @todo No error checking..
            $iSupport = [];

            $firstPos = (strpos($line->argData, ' ') + 1);
            $secondPos = strpos($line->argData, ' :') - $firstPos;

            $innerArg = substr($line->argData, $firstPos, $secondPos);

            $innerArg = explode(' ', $innerArg);
            foreach ($innerArg as $iArg) {
                if(strpos($iArg, '=') !== false) {
                    list($iargKey, $iargVal) = explode('=', $iArg, 2);
                    $iSupport[$iargKey] = $iargVal;
                } else {
                    $iSupport[$iArg] = '';
                }
            }

            $line->{$line->cmd} = $iSupport;

            return $line;
        }
    }

    /**
     * 006 RPL_MAP. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /* private function parse_rpl_map(StdClass $line) {
        return $line;
    } */

    /**
     * 007 RPL_MAPEND. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /* private function parse_rpl_mapend(StdClass $line) {
        return $line;
    } */

    /**
     * 008 RPL_SNOMASK. Originated from ircu.
     * Server notice mask (hex)
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_snomask(StdClass $line) {
        return $line;
    }*/

    /**
     * 009 RPL_STATMEMTOT. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statmemtot(StdClass $line) {
        return $line;
    }*/

    /**
     * 010 RPL_BOUNCE.
     * Sent to the client to redirect it to another server. Also known as RPL_REDIR
     * <hostname> <port> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_bounce(StdClass $line) {
        return $line;
    }*/

    /**
     * 010 RPL_STATMEM. Obsolete. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statmem(StdClass $line) {
        return $line;
    }*/

    /**
     * 014 RPL_YOURCOOKIE. Originated from Hybrid?.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_yourcookie(StdClass $line) {
        return $line;
    }*/

    /**
     * 015 RPL_MAP. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /* private function parse_rpl_map(StdClass $line) {
        return $line;
    } */

    /**
     * 016 RPL_MAPMORE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapmore(StdClass $line) {
        return $line;
    }*/

    /**
     * 017 RPL_MAPEND. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapend(StdClass $line) {
        return $line;
    }*/

    /**
     * 042 RPL_YOURID. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_yourid(StdClass $line) {
        return $line;
    }*/

    /**
     * 043 RPL_SAVENICK. Originated from IRCnet.
     * Sent to the client when their nickname was forced to change due to a collision
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_savenick(StdClass $line) {
        return $line;
    }*/

    /**
     * 050 RPL_ATTEMPTINGJUNC. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_attemptingjunc(StdClass $line) {
        return $line;
    }*/

    /**
     * 051 RPL_ATTEMPTINGREROUTE. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_attemptingreroute(StdClass $line) {
        return $line;
    }*/

    /*
     * Section 200-399, reply from server commands
     */

    /**
     * 200 RPL_TRACELINK. Originated from RFC1459.
     * See RFC
     * Link <version>[.<debug_level>] <destination> <next_server> [V<protocol_version> <link_uptime_in_seconds> <backstream_sendq> <upstream_sendq>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tracelink(StdClass $line) {
        return $line;
    }*/

    /**
     * 201 RPL_TRACECONNECTING. Originated from RFC1459.
     * See RFC
     * Try. <class> <server>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceconnecting(StdClass $line) {
        return $line;
    }*/

    /**
     * 202 RPL_TRACEHANDSHAKE. Originated from RFC1459.
     * See RFC
     * H.S. <class> <server>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tracehandshake(StdClass $line) {
        return $line;
    }*/

    /**
     * 203 RPL_TRACEUNKNOWN. Originated from RFC1459.
     * See RFC
     * ???? <class> [<connection_address>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceunknown(StdClass $line) {
        return $line;
    }*/

    /**
     * 204 RPL_TRACEOPERATOR. Originated from RFC1459.
     * See RFC
     * Oper <class> <nick>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceoperator(StdClass $line) {
        return $line;
    }*/

    /**
     * 205 RPL_TRACEUSER. Originated from RFC1459.
     * See RFC
     * User <class> <nick>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceuser(StdClass $line) {
        return $line;
    }*/

    /**
     * 206 RPL_TRACESERVER. Originated from RFC1459.
     * See RFC
     * Serv <class> <int>S <int>C <server> <nick!user|*!*>@<host|server> [V<protocol_version>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceserver(StdClass $line) {
        return $line;
    }*/

    /**
     * 207 RPL_TRACESERVICE. Originated from RFC2812.
     * See RFC
     * Service <class> <name> <type> <active_type>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 208 RPL_TRACENEWTYPE. Originated from RFC1459.
     * See RFC
     * <newtype> 0 <client_name>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tracenewtype(StdClass $line) {
        return $line;
    }*/

    /**
     * 209 RPL_TRACECLASS. Originated from RFC2812.
     * See RFC
     * Class <class> <count>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceclass(StdClass $line) {
        return $line;
    }*/

    /**
     * 210 RPL_TRACERECONNECT. Obsolete. Originated from RFC2812.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tracereconnect(StdClass $line) {
        return $line;
    }*/

    /**
     * 210 RPL_STATS. Originated from aircd.
     * Used instead of having multiple stats numerics
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_stats(StdClass $line) {
        return $line;
    }*/

    /**
     * 211 RPL_STATSLINKINFO. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * <linkname> <sendq> <sent_msgs> <sent_bytes> <recvd_msgs> <rcvd_bytes> <time_open>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statslinkinfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 212 RPL_STATSCOMMANDS. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * <command> <count> [<byte_count> <remote_count>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statscommands(StdClass $line) {
        return $line;
    }*/

    /**
     * 213 RPL_STATSCLINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * C <host> * <name> <port> <class>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statscline(StdClass $line) {
        return $line;
    }*/

    /**
     * 214 RPL_STATSNLINE. Conflicting. Originated from RFC1459.
     * Reply to STATS (See RFC), Also known as RPL_STATSOLDNLINE (ircu, Unreal)
     * N <host> * <name> <port> <class>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsnline(StdClass $line) {
        return $line;
    }*/

    /**
     * 215 RPL_STATSILINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * I <host> * <host> <port> <class>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsiline(StdClass $line) {
        return $line;
    }*/

    /**
     * 216 RPL_STATSKLINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * K <host> * <username> <port> <class>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statskline(StdClass $line) {
        return $line;
    }*/

    /**
     * 217 RPL_STATSQLINE. Conflicting. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsqline(StdClass $line) {
        return $line;
    }*/

    /**
     * 217 RPL_STATSPLINE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statspline(StdClass $line) {
        return $line;
    }*/

    /**
     * 218 RPL_STATSYLINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * Y <class> <ping_freq> <connect_freq> <max_sendq>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsyline(StdClass $line) {
        return $line;
    }*/

    /**
     * 219 RPL_ENDOFSTATS. Originated from RFC1459.
     * End of RPL_STATS* list.
     * <query> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofstats(StdClass $line) {
        return $line;
    }*/

    /**
     * 220 RPL_STATSPLINE. Conflicting. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statspline(StdClass $line) {
        return $line;
    }*/

    /**
     * 220 RPL_STATSBLINE. Conflicting. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsbline(StdClass $line) {
        return $line;
    }*/

    /**
     * 221 RPL_UMODEIS. Originated from RFC1459.
     * Information about a user's own modes. Some daemons have extended the mode command and certain modes take parameters (like channel modes).
     * <user_modes> [<user_mode_params>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_umodeis(StdClass $line) {
        return $line;
    }*/

    /**
     * 222 RPL_MODLIST. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_modlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 222 RPL_SQLINE_NICK. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_sqline_nick(StdClass $line) {
        return $line;
    }*/

    /**
     * 222 RPL_STATSBLINE. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsbline(StdClass $line) {
        return $line;
    }*/

    /**
     * 223 RPL_STATSELINE. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statseline(StdClass $line) {
        return $line;
    }*/

    /**
     * 223 RPL_STATSGLINE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsgline(StdClass $line) {
        return $line;
    }*/

    /**
     * 224 RPL_STATSFLINE. Conflicting. Originated from Hybrid, Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsfline(StdClass $line) {
        return $line;
    }*/

    /**
     * 224 RPL_STATSTLINE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statstline(StdClass $line) {
        return $line;
    }*/

    /**
     * 225 RPL_STATSDLINE. Conflicting. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdline(StdClass $line) {
        return $line;
    }*/

    /**
     * 225 RPL_STATSZLINE. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statszline(StdClass $line) {
        return $line;
    }*/

    /**
     * 225 RPL_STATSELINE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statseline(StdClass $line) {
        return $line;
    }*/

    /**
     * 226 RPL_STATSCOUNT. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statscount(StdClass $line) {
        return $line;
    }*/

    /**
     * 226 RPL_STATSNLINE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsnline(StdClass $line) {
        return $line;
    }*/

    /**
     * 227 RPL_STATSGLINE. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsgline(StdClass $line) {
        return $line;
    }*/

    /**
     * 227 RPL_STATSVLINE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsvline(StdClass $line) {
        return $line;
    }*/

    /**
     * 228 RPL_STATSQLINE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsqline(StdClass $line) {
        return $line;
    }*/

    /**
     * 231 RPL_SERVICEINFO. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_serviceinfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 232 RPL_ENDOFSERVICES. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofservices(StdClass $line) {
        return $line;
    }*/

    /**
     * 232 RPL_RULES. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_rules(StdClass $line) {
        return $line;
    }*/

    /**
     * 233 RPL_SERVICE. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_service(StdClass $line) {
        return $line;
    }*/

    /**
     * 234 RPL_SERVLIST. Originated from RFC2812.
     * A service entry in the service list
     * <name> <server> <mask> <type> <hopcount> <info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_servlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 235 RPL_SERVLISTEND. Originated from RFC2812.
     * Termination of an RPL_SERVLIST list
     * <mask> <type> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_servlistend(StdClass $line) {
        return $line;
    }*/

    /**
     * 236 RPL_STATSVERBOSE. Originated from ircu.
     * Verbose server list?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsverbose(StdClass $line) {
        return $line;
    }*/

    /**
     * 237 RPL_STATSENGINE. Originated from ircu.
     * Engine name?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsengine(StdClass $line) {
        return $line;
    }*/

    /**
     * 238 RPL_STATSFLINE. Conflicting. Originated from ircu.
     * Feature lines?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsfline(StdClass $line) {
        return $line;
    }*/

    /**
     * 239 RPL_STATSIAUTH. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsiauth(StdClass $line) {
        return $line;
    }*/

    /**
     * 240 RPL_STATSVLINE. Conflicting. Originated from RFC2812.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsvline(StdClass $line) {
        return $line;
    }*/

    /**
     * 240 RPL_STATSXLINE. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsxline(StdClass $line) {
        return $line;
    }*/

    /**
     * 241 RPL_STATSLLINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * L <hostmask> * <servername> <maxdepth>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statslline(StdClass $line) {
        return $line;
    }*/

    /**
     * 242 RPL_STATSUPTIME. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * :Server Up <days> days <hours>:<minutes>:<seconds>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsuptime(StdClass $line) {
        return $line;
    }*/

    /**
     * 243 RPL_STATSOLINE. Originated from RFC1459.
     * Reply to STATS (See RFC); The info field is an extension found in some IRC daemons, which returns info such as an e-mail address or the name/job of an operator
     * O <hostmask> * <nick> [:<info>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsoline(StdClass $line) {
        return $line;
    }*/

    /**
     * 244 RPL_STATSHLINE. Originated from RFC1459.
     * Reply to STATS (See RFC)
     * H <hostmask> * <servername>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statshline(StdClass $line) {
        return $line;
    }*/

    /**
     * 245 RPL_STATSSLINE. Originated from Bahamut, IRCnet, Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statssline(StdClass $line) {
        return $line;
    }*/

    /**
     * 246 RPL_STATSPING. Obsolete. Originated from RFC2812.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsping(StdClass $line) {
        return $line;
    }*/

    /**
     * 246 RPL_STATSTLINE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statstline(StdClass $line) {
        return $line;
    }*/

    /**
     * 246 RPL_STATSULINE. Conflicting. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsuline(StdClass $line) {
        return $line;
    }*/

    /**
     * 247 RPL_STATSBLINE. Conflicting. Originated from RFC2812.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsbline(StdClass $line) {
        return $line;
    }*/

    /**
     * 247 RPL_STATSXLINE. Conflicting. Originated from Hybrid, PTlink, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsxline(StdClass $line) {
        return $line;
    }*/

    /**
     * 247 RPL_STATSGLINE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsgline(StdClass $line) {
        return $line;
    }*/

    /**
     * 248 RPL_STATSULINE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsuline(StdClass $line) {
        return $line;
    }*/

    /**
     * 248 RPL_STATSDEFINE. Conflicting. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdefine(StdClass $line) {
        return $line;
    }*/

    /**
     * 249 RPL_STATSULINE. Conflicting.
     * Extension to RFC1459?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsuline(StdClass $line) {
        return $line;
    }*/

    /**
     * 249 RPL_STATSDEBUG. Conflicting. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdebug(StdClass $line) {
        return $line;
    }*/

    /**
     * 250 RPL_STATSDLINE. Conflicting. Originated from RFC2812.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdline(StdClass $line) {
        return $line;
    }*/

    /**
     * 250 RPL_STATSCONN. Originated from ircu, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsconn(StdClass $line) {
        return $line;
    }*/

    /**
     * 251 RPL_LUSERCLIENT. Originated from RFC1459.
     * Reply to LUSERS command, other versions exist (eg. RFC2812); Text may vary.
     * :There are <int> users and <int> invisible on <int> servers
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_luserclient(StdClass $line) {
        if(!preg_match('/There are (.*) users and (.*) invisible on (.*) servers/i', $line->args[1], $matches)) {
            // Try again a little more loosely..
            if(!preg_match_all('/(\d+)/', $line->args[1], $matches)) {
                trigger_error("Could not parse RPL_LUSERCLIENT in Parser", E_USER_WARNING);

                return $line;
            } else {
                $luserclient = [
                    'users'     => $matches[0][0],
                    'invisible' => $matches[0][1],
                    'servers'   => $matches[0][2],
                ];
            }
        } else {
            $luserclient = [
                'users'     => $matches[1],
                'invisible' => $matches[2],
                'servers'   => $matches[3],
            ];
        }

        $line->{$line->cmd} = $luserclient;

        return $line;
    }

    /**
     * 252 RPL_LUSEROP. Originated from RFC1459.
     * Reply to LUSERS command - Number of IRC operators online
     * <int> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_luserop(StdClass $line) {
        $line->{$line->cmd} = [
            'ircop-online' => $line->args[1]
        ];

        return $line;
    }

    /**
     * 253 RPL_LUSERUNKNOWN. Originated from RFC1459.
     * Reply to LUSERS command - Number of unknown/unregistered connections
     * <int> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_luserunknown(StdClass $line) {
        $line->{$line->cmd} = [
            'unknown-connections' => $line->args[1]
        ];

        return $line;
    }

    /**
     * 254 RPL_LUSERCHANNELS. Originated from RFC1459.
     * Reply to LUSERS command - Number of channels formed
     * <int> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_luserchannels(StdClass $line) {
        $line->{$line->cmd} = [
            'channels-formed' => $line->args[1]
        ];

        return $line;
    }

    /**
     * 255 RPL_LUSERME. Originated from RFC1459.
     * Reply to LUSERS command - Information about local connections; Text may vary.
     * :I have <int> clients and <int> servers
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_luserme(StdClass $line) {
        if(!preg_match('/I have (.*) clients and (.*) servers/i', $line->args[1], $matches)) {
            // Try again a little more loosely..
            if(!preg_match_all('/(\d+)/', $line->args[1], $matches)) {
                trigger_error("Could not parse RPL_LUSERME in Parser", E_USER_WARNING);

                return $line;
            } else {
                $luserme = [
                    'clients' => $matches[0][0],
                    'servers' => $matches[0][1],
                ];
            }
        } else {
            $luserme = [
                'clients' => $matches[1],
                'servers' => $matches[2],
            ];
        }

        $line->{$line->cmd} = $luserme;

        return $line;
    }

    /**
     * 256 RPL_ADMINME. Originated from RFC1459.
     * Start of an RPL_ADMIN* reply. In practise, the server parameter is often never given, and instead the info field contains the text 'Administrative info about <server>'. Newer daemons seem to follow the RFC and output the server's hostname in the 'server' parameter, but also output the server name in the text as per traditional daemons.
     * <server> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_adminme(StdClass $line) {
        return $line;
    }*/

    /**
     * 257 RPL_ADMINLOC1. Originated from RFC1459.
     * Reply to ADMIN command (Location, first line)
     * :<admin_location>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_adminloc1(StdClass $line) {
        return $line;
    }*/

    /**
     * 258 RPL_ADMINLOC2. Originated from RFC1459.
     * Reply to ADMIN command (Location, second line)
     * :<admin_location>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_adminloc2(StdClass $line) {
        return $line;
    }*/

    /**
     * 259 RPL_ADMINEMAIL. Originated from RFC1459.
     * Reply to ADMIN command (E-mail address of administrator)
     * :<email_address>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_adminemail(StdClass $line) {
        return $line;
    }*/

    /**
     * 261 RPL_TRACELOG. Originated from RFC1459.
     * See RFC
     * File <logfile> <debug_level>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tracelog(StdClass $line) {
        return $line;
    }*/

    /**
     * 262 RPL_TRACEPING. Conflicting.
     * Extension to RFC1459?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceping(StdClass $line) {
        return $line;
    }*/

    /**
     * 262 RPL_TRACEEND. Conflicting. Originated from RFC2812.
     * Used to terminate a list of RPL_TRACE* replies
     * <server_name> <version>[.<debug_level>] :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceend(StdClass $line) {
        return $line;
    }*/

    /**
     * 263 RPL_TRYAGAIN. Originated from RFC2812.
     * When a server drops a command without processing it, it MUST use this reply. Also known as RPL_LOAD_THROTTLED and RPL_LOAD2HI, I'm presuming they do the same thing.
     * <command> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_tryagain(StdClass $line) {
        return $line;
    }*/

    /**
     * 265 RPL_LOCALUSERS. Originated from aircd, Hybrid, Hybrid, Bahamut.
     * Also known as RPL_CURRENT_LOCAL
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_localusers(StdClass $line) {

        $line->{$line->cmd} = [
            'local-users'     => $line->args[1],
            'max-local-users' => $line->args[2],
        ];

        return $line;
    }

    /**
     * 266 RPL_GLOBALUSERS. Originated from aircd, Hybrid, Hybrid, Bahamut.
     * Also known as RPL_CURRENT_GLOBAL
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_globalusers(StdClass $line) {

        $line->{$line->cmd} = [
            'global-users'     => $line->args[1],
            'max-global-users' => $line->args[2],
        ];

        return $line;
    }

    /**
     * 267 RPL_START_NETSTAT. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_start_netstat(StdClass $line) {
        return $line;
    }*/

    /**
     * 268 RPL_NETSTAT. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_netstat(StdClass $line) {
        return $line;
    }*/

    /**
     * 269 RPL_END_NETSTAT. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_end_netstat(StdClass $line) {
        return $line;
    }*/

    /**
     * 270 RPL_PRIVS. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_privs(StdClass $line) {
        return $line;
    }*/

    /**
     * 271 RPL_SILELIST. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_silelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 272 RPL_ENDOFSILELIST. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofsilelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 273 RPL_NOTIFY. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_notify(StdClass $line) {
        return $line;
    }*/

    /**
     * 274 RPL_ENDNOTIFY. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endnotify(StdClass $line) {
        return $line;
    }*/

    /**
     * 274 RPL_STATSDELTA. Conflicting. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdelta(StdClass $line) {
        return $line;
    }*/

    /**
     * 275 RPL_STATSDLINE. Conflicting. Originated from ircu, Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_statsdline(StdClass $line) {
        return $line;
    }*/

    /**
     * 276 RPL_VCHANEXIST.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_vchanexist(StdClass $line) {
        return $line;
    }*/

    /**
     * 277 RPL_VCHANLIST.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_vchanlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 278 RPL_VCHANHELP.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_vchanhelp(StdClass $line) {
        return $line;
    }*/

    /**
     * 280 RPL_GLIST. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_glist(StdClass $line) {
        return $line;
    }*/

    /**
     * 281 RPL_ENDOFGLIST. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofglist(StdClass $line) {
        return $line;
    }*/

    /**
     * 281 RPL_ACCEPTLIST. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_acceptlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 282 RPL_ENDOFACCEPT. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofaccept(StdClass $line) {
        return $line;
    }*/

    /**
     * 282 RPL_JUPELIST. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_jupelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 283 RPL_ALIST. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_alist(StdClass $line) {
        return $line;
    }*/

    /**
     * 283 RPL_ENDOFJUPELIST. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofjupelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 284 RPL_ENDOFALIST. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofalist(StdClass $line) {
        return $line;
    }*/

    /**
     * 284 RPL_FEATURE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_feature(StdClass $line) {
        return $line;
    }*/

    /**
     * 285 RPL_GLIST_HASH. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_glist_hash(StdClass $line) {
        return $line;
    }*/

    /**
     * 285 RPL_CHANINFO_HANDLE. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_handle(StdClass $line) {
        return $line;
    }*/

    /**
     * 285 RPL_NEWHOSTIS. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_newhostis(StdClass $line) {
        return $line;
    }*/

    /**
     * 286 RPL_CHANINFO_USERS. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_users(StdClass $line) {
        return $line;
    }*/

    /**
     * 286 RPL_CHKHEAD. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chkhead(StdClass $line) {
        return $line;
    }*/

    /**
     * 287 RPL_CHANINFO_CHOPS. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_chops(StdClass $line) {
        return $line;
    }*/

    /**
     * 287 RPL_CHANUSER. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chanuser(StdClass $line) {
        return $line;
    }*/

    /**
     * 288 RPL_CHANINFO_VOICES. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_voices(StdClass $line) {
        return $line;
    }*/

    /**
     * 288 RPL_PATCHHEAD. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_patchhead(StdClass $line) {
        return $line;
    }*/

    /**
     * 289 RPL_CHANINFO_AWAY. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_away(StdClass $line) {
        return $line;
    }*/

    /**
     * 289 RPL_PATCHCON. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_patchcon(StdClass $line) {
        return $line;
    }*/

    /**
     * 290 RPL_CHANINFO_OPERS. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_opers(StdClass $line) {
        return $line;
    }*/

    /**
     * 290 RPL_HELPHDR. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helphdr(StdClass $line) {
        return $line;
    }*/

    /**
     * 290 RPL_DATASTR. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_datastr(StdClass $line) {
        return $line;
    }*/

    /**
     * 291 RPL_CHANINFO_BANNED. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_banned(StdClass $line) {
        return $line;
    }*/

    /**
     * 291 RPL_HELPOP. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helpop(StdClass $line) {
        return $line;
    }*/

    /**
     * 291 RPL_ENDOFCHECK. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofcheck(StdClass $line) {
        return $line;
    }*/

    /**
     * 292 RPL_CHANINFO_BANS. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_bans(StdClass $line) {
        return $line;
    }*/

    /**
     * 292 RPL_HELPTLR. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helptlr(StdClass $line) {
        return $line;
    }*/

    /**
     * 293 RPL_CHANINFO_INVITE. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_invite(StdClass $line) {
        return $line;
    }*/

    /**
     * 293 RPL_HELPHLP. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helphlp(StdClass $line) {
        return $line;
    }*/

    /**
     * 294 RPL_CHANINFO_INVITES. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_invites(StdClass $line) {
        return $line;
    }*/

    /**
     * 294 RPL_HELPFWD. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helpfwd(StdClass $line) {
        return $line;
    }*/

    /**
     * 295 RPL_CHANINFO_KICK. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_kick(StdClass $line) {
        return $line;
    }*/

    /**
     * 295 RPL_HELPIGN. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helpign(StdClass $line) {
        return $line;
    }*/

    /**
     * 296 RPL_CHANINFO_KICKS. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chaninfo_kicks(StdClass $line) {
        return $line;
    }*/

    /**
     * 299 RPL_END_CHANINFO. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_end_chaninfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 300 RPL_NONE. Originated from RFC1459.
     * Dummy reply, supposedly only used for debugging/testing new features, however has appeared in production daemons.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_none(StdClass $line) {
        return $line;
    }*/

    /**
     * 301 RPL_AWAY. Originated from RFC1459.
     * Used in reply to a command directed at a user who is marked as away
     * <nick> :<message>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_away(StdClass $line) {
        return $line;
    }*/

    /**
     * 301 RPL_AWAY. Multiple responses mapped. Originated from KineIRCd.
     * Identical to RPL_AWAY, however this includes the number of seconds the user has been away for. This is designed to discourage the need for people to use those horrible scripts which set the AWAY message every 30 seconds in order to include an 'away since' timer.
     * <nick> <seconds away> :<message>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_away(StdClass $line) {
        return $line;
    }*/

    /**
     * 302 RPL_USERHOST. Originated from RFC1459.
     * Reply used by USERHOST (see RFC)
     * :*1<reply> *( ' ' <reply> )
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_userhost(StdClass $line) {
        return $line;
    }*/

    /**
     * 303 RPL_ISON. Originated from RFC1459.
     * Reply to the ISON command (see RFC)
     * :*1<nick> *( ' ' <nick> )
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_ison(StdClass $line) {
        return $line;
    }*/

    /**
     * 304 RPL_TEXT. Obsolete.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_text(StdClass $line) {
        return $line;
    }*/

    /**
     * 305 RPL_UNAWAY. Originated from RFC1459.
     * Reply from AWAY when no longer marked as away
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_unaway(StdClass $line) {
        return $line;
    }*/

    /**
     * 306 RPL_NOWAWAY. Originated from RFC1459.
     * Reply from AWAY when marked away
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nowaway(StdClass $line) {
        return $line;
    }*/

    /**
     * 307 RPL_USERIP. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_userip(StdClass $line) {
        return $line;
    }*/

    /**
     * 307 RPL_WHOISREGNICK. Conflicting. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisregnick(StdClass $line) {
        return $line;
    }*/

    /**
     * 307 RPL_SUSERHOST. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_suserhost(StdClass $line) {
        return $line;
    }*/

    /**
     * 308 RPL_NOTIFYACTION. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_notifyaction(StdClass $line) {
        return $line;
    }*/

    /**
     * 308 RPL_WHOISADMIN. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisadmin(StdClass $line) {
        return $line;
    }*/

    /**
     * 308 RPL_RULESSTART. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_rulesstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 309 RPL_NICKTRACE. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nicktrace(StdClass $line) {
        return $line;
    }*/

    /**
     * 309 RPL_WHOISSADMIN. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoissadmin(StdClass $line) {
        return $line;
    }*/

    /**
     * 309 RPL_ENDOFRULES. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofrules(StdClass $line) {
        return $line;
    }*/

    /**
     * 309 RPL_WHOISHELPER. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoishelper(StdClass $line) {
        return $line;
    }*/

    /**
     * 310 RPL_WHOISSVCMSG. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoissvcmsg(StdClass $line) {
        return $line;
    }*/

    /**
     * 310 RPL_WHOISHELPOP. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoishelpop(StdClass $line) {
        return $line;
    }*/

    /**
     * 310 RPL_WHOISSERVICE. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 311 RPL_WHOISUSER. Originated from RFC1459.
     * Reply to WHOIS - Information about the user
     * <nick> <user> <host> * :<real_name>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisuser(StdClass $line) {
        return $line;
    }*/

    /**
     * 312 RPL_WHOISSERVER. Originated from RFC1459.
     * Reply to WHOIS - What server they're on
     * <nick> <server> :<server_info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisserver(StdClass $line) {
        return $line;
    }*/

    /**
     * 313 RPL_WHOISOPERATOR. Originated from RFC1459.
     * Reply to WHOIS - User has IRC Operator privileges
     * <nick> :<privileges>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisoperator(StdClass $line) {
        return $line;
    }*/

    /**
     * 314 RPL_WHOWASUSER. Originated from RFC1459.
     * Reply to WHOWAS - Information about the user
     * <nick> <user> <host> * :<real_name>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whowasuser(StdClass $line) {
        return $line;
    }*/

    /**
     * 315 RPL_ENDOFWHO. Originated from RFC1459.
     * Used to terminate a list of RPL_WHOREPLY replies
     * <name> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofwho(StdClass $line) {
        return $line;
    }*/

    /**
     * 316 RPL_WHOISCHANOP. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoischanop(StdClass $line) {
        return $line;
    }*/

    /**
     * 317 RPL_WHOISIDLE. Originated from RFC1459.
     * Reply to WHOIS - Idle information
     * <nick> <seconds> :seconds idle
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisidle(StdClass $line) {
        return $line;
    }*/

    /**
     * 318 RPL_ENDOFWHOIS. Originated from RFC1459.
     * Reply to WHOIS - End of list
     * <nick> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofwhois(StdClass $line) {
        return $line;
    }*/

    /**
     * 319 RPL_WHOISCHANNELS. Originated from RFC1459.
     * Reply to WHOIS - Channel list for user (See RFC)
     * <nick> :*( ( '@' / '+' ) <channel> ' ' )
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoischannels(StdClass $line) {
        return $line;
    }*/

    /**
     * 320 RPL_WHOISVIRT. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisvirt(StdClass $line) {
        return $line;
    }*/

    /**
     * 320 RPL_WHOIS_HIDDEN. Originated from Anothernet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whois_hidden(StdClass $line) {
        return $line;
    }*/

    /**
     * 320 RPL_WHOISSPECIAL. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisspecial(StdClass $line) {
        return $line;
    }*/

    /**
     * 321 RPL_LISTSTART. Obsolete. Originated from RFC1459.
     * Channel list - Header
     * Channels :Users  Name
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_liststart(StdClass $line) {
        return $line;
    }*/

    /**
     * 322 RPL_LIST. Originated from RFC1459.
     * Channel list - A channel
     * <channel> <#_visible> :<topic>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_list(StdClass $line) {
        return $line;
    }*/

    /**
     * 323 RPL_LISTEND. Originated from RFC1459.
     * Channel list - End of list
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_listend(StdClass $line) {
        return $line;
    }*/

    /**
     * 324 RPL_CHANNELMODEIS. Originated from RFC1459.
     * <channel> <mode> <mode_params>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_channelmodeis(StdClass $line) {
        return $line;
    }*/

    /**
     * 325 RPL_UNIQOPIS. Conflicting. Originated from RFC2812.
     * <channel> <nickname>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_uniqopis(StdClass $line) {
        return $line;
    }*/

    /**
     * 325 RPL_CHANNELPASSIS. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_channelpassis(StdClass $line) {
        return $line;
    }*/

    /**
     * 326 RPL_NOCHANPASS.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nochanpass(StdClass $line) {
        return $line;
    }*/

    /**
     * 327 RPL_CHPASSUNKNOWN.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chpassunknown(StdClass $line) {
        return $line;
    }*/

    /**
     * 328 RPL_CHANNEL_URL. Originated from Bahamut, AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_channel_url(StdClass $line) {
        return $line;
    }*/

    /**
     * 329 RPL_CREATIONTIME. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_creationtime(StdClass $line) {
        return $line;
    }*/

    /**
     * 330 RPL_WHOWAS_TIME. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whowas_time(StdClass $line) {
        return $line;
    }*/

    /**
     * 330 RPL_WHOISACCOUNT. Conflicting. Originated from ircu.
     * <nick> <authname> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisaccount(StdClass $line) {
        return $line;
    }*/

    /**
     * 331 RPL_NOTOPIC. Originated from RFC1459.
     * Response to TOPIC when no topic is set
     * <channel> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_notopic(StdClass $line) {
        return $line;
    }*/

    /**
     * 332 RPL_TOPIC. Originated from RFC1459.
     * Response to TOPIC with the set topic
     * <channel> :<topic>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_topic(StdClass $line) {
        $line->{$line->cmd} = [
            'channel' => $line->args[1],
            'topic'   => $line->args[2],
        ];

        return $line;
    }

    /**
     * 333 RPL_TOPICWHOTIME. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_topicwhotime(StdClass $line) {

        $time = new \DateTime();
        $time->setTimestamp($line->args[3]);

        $whotime = [
            'channel'  => $line->args[1],
            'who'      => $this->parseIrcHostmask($line->args[2]),
            'dateTime' => $time,
        ];

        return $line;
    }

    /**
     * 334 RPL_LISTUSAGE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_listusage(StdClass $line) {
        return $line;
    }*/

    /**
     * 334 RPL_COMMANDSYNTAX. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_commandsyntax(StdClass $line) {
        return $line;
    }*/

    /**
     * 334 RPL_LISTSYNTAX. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_listsyntax(StdClass $line) {
        return $line;
    }*/

    /**
     * 335 RPL_WHOISBOT. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisbot(StdClass $line) {
        return $line;
    }*/

    /**
     * 338 RPL_CHANPASSOK. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chanpassok(StdClass $line) {
        return $line;
    }*/

    /**
     * 338 RPL_WHOISACTUALLY. Conflicting. Originated from ircu, Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisactually(StdClass $line) {
        return $line;
    }*/

    /**
     * 339 RPL_BADCHANPASS.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_badchanpass(StdClass $line) {
        return $line;
    }*/

    /**
     * 340 RPL_USERIP. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_userip(StdClass $line) {
        return $line;
    }*/

    /**
     * 341 RPL_INVITING. Originated from RFC1459.
     * Returned by the server to indicate that the attempted INVITE message was successful and is being passed onto the end client. Note that RFC1459 documents the parameters in the reverse order. The format given here is the format used on production servers, and should be considered the standard reply above that given by RFC1459.
     * <nick> <channel>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_inviting(StdClass $line) {
        return $line;
    }*/

    /**
     * 342 RPL_SUMMONING. Obsolete. Originated from RFC1459.
     * Returned by a server answering a SUMMON message to indicate that it is summoning that user
     * <user> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_summoning(StdClass $line) {
        return $line;
    }*/

    /**
     * 345 RPL_INVITED. Originated from GameSurge.
     * Sent to users on a channel when an INVITE command has been issued
     * <channel> <user being invited> <user issuing invite> :<user being invited> has been invited by <user issuing invite>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_invited(StdClass $line) {
        return $line;
    }*/

    /**
     * 346 RPL_INVITELIST. Originated from RFC2812.
     * An invite mask for the invite mask list
     * <channel> <invitemask>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_invitelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 347 RPL_ENDOFINVITELIST. Originated from RFC2812.
     * Termination of an RPL_INVITELIST list
     * <channel> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofinvitelist(StdClass $line) {
        return $line;
    }*/

    /**
     * 348 RPL_EXCEPTLIST. Originated from RFC2812.
     * An exception mask for the exception mask list. Also known as RPL_EXLIST (Unreal, Ultimate)
     * <channel> <exceptionmask>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_exceptlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 349 RPL_ENDOFEXCEPTLIST. Originated from RFC2812.
     * Termination of an RPL_EXCEPTLIST list. Also known as RPL_ENDOFEXLIST (Unreal, Ultimate)
     * <channel> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofexceptlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 351 RPL_VERSION. Originated from RFC1459.
     * Reply by the server showing its version details, however this format is not often adhered to
     * <version>[.<debuglevel>] <server> :<comments>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_version(StdClass $line) {
        return $line;
    }*/

    /**
     * 352 RPL_WHOREPLY. Originated from RFC1459.
     * Reply to vanilla WHO (See RFC). This format can be very different if the 'WHOX' version of the command is used (see ircu).
     * <channel> <user> <host> <server> <nick> <H|G>[*][@|+] :<hopcount> <real_name>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoreply(StdClass $line) {
        return $line;
    }*/

    /**
     * 353 RPL_NAMREPLY. Originated from RFC1459.
     * Reply to NAMES (See RFC)
     * ( '=' / '*' / '@' ) <channel> ' ' : [ '@' / '+' ] <nick> *( ' ' [ '@' / '+' ] <nick> )
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    private function parse_rpl_namreply(StdClass $line) {

        if($line->args[1] == '=') {
            $visibility = 'secret';
        } elseif($line->args[1] == '*') {
            $visibility = 'private';
        } elseif($line->args[1] == '=') {
            $visibility = 'global';
        } else {
            // Unexpected...
            trigger_error("Unexpected visibility: {$line->args[1]} while parsing RPL_NAMREPLY", E_USER_WARNING);
            $visibility = 'other';
        }

        $namReply = [
            'visibility' => $visibility,
            'channel' => $line->args[2],
            'names' => explode(' ', $line->args[3]),
        ];

        $line->{$line->cmd} = $namReply;

        return $line;
    }

    /**
     * 354 RPL_WHOSPCRPL. Originated from ircu.
     * Reply to WHO, however it is a 'special' reply because it is returned using a non-standard (non-RFC1459) format. The format is dictated by the command given by the user, and can vary widely. When this is used, the WHO command was invoked in its 'extended' form, as announced by the 'WHOX' ISUPPORT tag.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whospcrpl(StdClass $line) {
        return $line;
    }*/

    /**
     * 355 RPL_NAMREPLY_. Originated from QuakeNet.
     * See also command 353.
     * Reply to the "NAMES -d" command - used to show invisible users (when the channel is set +D, QuakeNet relative). The proper define name for this numeric is unknown at this time
     * ( '=' / '*' / '@' ) <channel> ' ' : [ '@' / '+' ] <nick> *( ' ' [ '@' / '+' ] <nick> )
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_namreply_(StdClass $line) {
        return $line;
    }*/

    /**
     * 357 RPL_MAP. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /* private function parse_rpl_map(StdClass $line) {
        return $line;
    } */

    /**
     * 358 RPL_MAPMORE. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapmore(StdClass $line) {
        return $line;
    }*/

    /**
     * 359 RPL_MAPEND. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapend(StdClass $line) {
        return $line;
    }*/

    /**
     * 361 RPL_KILLDONE. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_killdone(StdClass $line) {
        return $line;
    }*/

    /**
     * 362 RPL_CLOSING. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_closing(StdClass $line) {
        return $line;
    }*/

    /**
     * 363 RPL_CLOSEEND. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_closeend(StdClass $line) {
        return $line;
    }*/

    /**
     * 364 RPL_LINKS. Originated from RFC1459.
     * Reply to the LINKS command
     * <mask> <server> :<hopcount> <server_info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_links(StdClass $line) {
        return $line;
    }*/

    /**
     * 365 RPL_ENDOFLINKS. Originated from RFC1459.
     * Termination of an RPL_LINKS list
     * <mask> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endoflinks(StdClass $line) {
        return $line;
    }*/

    /**
     * 366 RPL_ENDOFNAMES. Originated from RFC1459.
     * Termination of an RPL_NAMREPLY list
     * <channel> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofnames(StdClass $line) {
        return $line;
    }*/

    /**
     * 367 RPL_BANLIST. Originated from RFC1459.
     * A ban-list item (See RFC); <time left> and <reason> are additions used by KineIRCd
     * <channel> <banid> [<time_left> :<reason>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_banlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 368 RPL_ENDOFBANLIST. Originated from RFC1459.
     * Termination of an RPL_BANLIST list
     * <channel> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofbanlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 369 RPL_ENDOFWHOWAS. Originated from RFC1459.
     * Reply to WHOWAS - End of list
     * <nick> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofwhowas(StdClass $line) {
        return $line;
    }*/

    /**
     * 371 RPL_INFO. Originated from RFC1459.
     * Reply to INFO
     * :<string>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_info(StdClass $line) {
        return $line;
    }*/

    /**
     * 372 RPL_MOTD. Originated from RFC1459.
     * Reply to MOTD
     * :- <string>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_motd(StdClass $line) {
        return $line;
    }*/

    /**
     * 373 RPL_INFOSTART. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_infostart(StdClass $line) {
        return $line;
    }*/

    /**
     * 374 RPL_ENDOFINFO. Originated from RFC1459.
     * Termination of an RPL_INFO list
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofinfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 375 RPL_MOTDSTART. Originated from RFC1459.
     * Start of an RPL_MOTD list
     * :- <server> Message of the day -
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_motdstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 376 RPL_ENDOFMOTD. Originated from RFC1459.
     * Termination of an RPL_MOTD list
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofmotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 377 RPL_KICKEXPIRED. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_kickexpired(StdClass $line) {
        return $line;
    }*/

    /**
     * 377 RPL_SPAM. Obsolete. Originated from AustHex.
     * Used during the connection (after MOTD) to announce the network policy on spam and privacy. Supposedly now obsoleted in favour of using NOTICE.
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_spam(StdClass $line) {
        return $line;
    }*/

    /**
     * 378 RPL_BANEXPIRED. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_banexpired(StdClass $line) {
        return $line;
    }*/

    /**
     * 378 RPL_WHOISHOST. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoishost(StdClass $line) {
        return $line;
    }*/

    /**
     * 378 RPL_MOTD. Obsolete. Conflicting. Originated from AustHex.
     * See also command 372.
     * Used by AustHex to 'force' the display of the MOTD, however is considered obsolete due to client/script awareness & ability to
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_motd(StdClass $line) {
        return $line;
    }*/

    /**
     * 379 RPL_KICKLINKED. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_kicklinked(StdClass $line) {
        return $line;
    }*/

    /**
     * 379 RPL_WHOISMODES. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoismodes(StdClass $line) {
        return $line;
    }*/

    /**
     * 380 RPL_BANLINKED. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_banlinked(StdClass $line) {
        return $line;
    }*/

    /**
     * 380 RPL_YOURHELPER. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_yourhelper(StdClass $line) {
        return $line;
    }*/

    /**
     * 381 RPL_YOUREOPER. Originated from RFC1459.
     * Successful reply from OPER
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_youreoper(StdClass $line) {
        return $line;
    }*/

    /**
     * 382 RPL_REHASHING. Originated from RFC1459.
     * Successful reply from REHASH
     * <config_file> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_rehashing(StdClass $line) {
        return $line;
    }*/

    /**
     * 383 RPL_YOURESERVICE. Originated from RFC2812.
     * Sent upon successful registration of a service
     * :You are service <service_name>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_youreservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 384 RPL_MYPORTIS. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_myportis(StdClass $line) {
        return $line;
    }*/

    /**
     * 385 RPL_NOTOPERANYMORE. Originated from AustHex, Hybrid, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_notoperanymore(StdClass $line) {
        return $line;
    }*/

    /**
     * 386 RPL_QLIST. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_qlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 386 RPL_IRCOPS. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_ircops(StdClass $line) {
        return $line;
    }*/

    /**
     * 387 RPL_ENDOFQLIST. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofqlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 387 RPL_ENDOFIRCOPS. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofircops(StdClass $line) {
        return $line;
    }*/

    /**
     * 388 RPL_ALIST. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_alist(StdClass $line) {
        return $line;
    }*/

    /**
     * 389 RPL_ENDOFALIST. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofalist(StdClass $line) {
        return $line;
    }*/

    /**
     * 391 RPL_TIME. Originated from RFC1459.
     * See also command 679.
     * Response to the TIME command. The string format may vary greatly.
     * <server> :<time string>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_time(StdClass $line) {
        return $line;
    }*/

    /**
     * 391 RPL_TIME. Conflicting. Multiple responses mapped. Originated from ircu.
     * This extention adds the timestamp and timestamp-offet information for clients.
     * <server> <timestamp> <offset> :<time string>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_time(StdClass $line) {
        return $line;
    }*/

    /**
     * 391 RPL_TIME. Conflicting. Multiple responses mapped. Originated from bdq-ircd.
     * Timezone name is acronym style (eg. 'EST', 'PST' etc). The microseconds field is the number of microseconds since the UNIX epoch, however it is relative to the local timezone of the server. The timezone field is ambiguous, since it only appears to include American zones.
     * <server> <timezone name> <microseconds> :<time string>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_time(StdClass $line) {
        return $line;
    }*/

    /**
     * 391 RPL_TIME. Conflicting. Multiple responses mapped.
     * Yet another variation, including the time broken down into its components. Time is supposedly relative to UTC.
     * <server> <year> <month> <day> <hour> <minute> <second>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_time(StdClass $line) {
        return $line;
    }*/

    /**
     * 392 RPL_USERSSTART. Originated from RFC1459.
     * Start of an RPL_USERS list
     * :UserID   Terminal  Host
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_usersstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 393 RPL_USERS. Originated from RFC1459.
     * Response to the USERS command (See RFC)
     * :<username> <ttyline> <hostname>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_users(StdClass $line) {
        return $line;
    }*/

    /**
     * 394 RPL_ENDOFUSERS. Originated from RFC1459.
     * Termination of an RPL_USERS list
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofusers(StdClass $line) {
        return $line;
    }*/

    /**
     * 395 RPL_NOUSERS. Originated from RFC1459.
     * Reply to USERS when nobody is logged in
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nousers(StdClass $line) {
        return $line;
    }*/

    /**
     * 396 RPL_HOSTHIDDEN. Originated from Undernet.
     * Reply to a user when user mode +x (host masking) was set successfully
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_hosthidden(StdClass $line) {
        return $line;
    }*/

    /*
     * Section 400-599, errors
     */

    /**
     * 400 ERR_UNKNOWNERROR.
     * Sent when an error occured executing a command, but it is not specifically known why the command could not be executed.
     * <command> [<?>] :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_unknownerror(StdClass $line) {
        return $line;
    }*/

    /**
     * 401 ERR_NOSUCHNICK. Originated from RFC1459.
     * Used to indicate the nickname parameter supplied to a command is currently unused
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nosuchnick(StdClass $line) {
        return $line;
    }*/

    /**
     * 402 ERR_NOSUCHSERVER. Originated from RFC1459.
     * Used to indicate the server name given currently doesn't exist
     * <server> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nosuchserver(StdClass $line) {
        return $line;
    }*/

    /**
     * 403 ERR_NOSUCHCHANNEL. Originated from RFC1459.
     * Used to indicate the given channel name is invalid, or does not exist
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nosuchchannel(StdClass $line) {
        return $line;
    }*/

    /**
     * 404 ERR_CANNOTSENDTOCHAN. Originated from RFC1459.
     * Sent to a user who does not have the rights to send a message to a channel
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotsendtochan(StdClass $line) {
        return $line;
    }*/

    /**
     * 405 ERR_TOOMANYCHANNELS. Originated from RFC1459.
     * Sent to a user when they have joined the maximum number of allowed channels and they tried to join another channel
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanychannels(StdClass $line) {
        return $line;
    }*/

    /**
     * 406 ERR_WASNOSUCHNICK. Originated from RFC1459.
     * Returned by WHOWAS to indicate there was no history information for a given nickname
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_wasnosuchnick(StdClass $line) {
        return $line;
    }*/

    /**
     * 407 ERR_TOOMANYTARGETS. Originated from RFC1459.
     * The given target(s) for a command are ambiguous in that they relate to too many targets
     * <target> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanytargets(StdClass $line) {
        return $line;
    }*/

    /**
     * 408 ERR_NOSUCHSERVICE. Originated from RFC2812.
     * Returned to a client which is attempting to send an SQUERY (or other message) to a service which does not exist
     * <service_name> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nosuchservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 408 ERR_NOCOLORSONCHAN. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nocolorsonchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 409 ERR_NOORIGIN. Originated from RFC1459.
     * PING or PONG message missing the originator parameter which is required since these commands must work without valid prefixes
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noorigin(StdClass $line) {
        return $line;
    }*/

    /**
     * 411 ERR_NORECIPIENT. Originated from RFC1459.
     * Returned when no recipient is given with a command
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_norecipient(StdClass $line) {
        return $line;
    }*/

    /**
     * 412 ERR_NOTEXTTOSEND. Originated from RFC1459.
     * Returned when NOTICE/PRIVMSG is used with no message given
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notexttosend(StdClass $line) {
        return $line;
    }*/

    /**
     * 413 ERR_NOTOPLEVEL. Originated from RFC1459.
     * Used when a message is being sent to a mask without being limited to a top-level domain (i.e. * instead of *.au)
     * <mask> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notoplevel(StdClass $line) {
        return $line;
    }*/

    /**
     * 414 ERR_WILDTOPLEVEL. Originated from RFC1459.
     * Used when a message is being sent to a mask with a wild-card for a top level domain (i.e. *.*)
     * <mask> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_wildtoplevel(StdClass $line) {
        return $line;
    }*/

    /**
     * 415 ERR_BADMASK. Originated from RFC2812.
     * Used when a message is being sent to a mask with an invalid syntax
     * <mask> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badmask(StdClass $line) {
        return $line;
    }*/

    /**
     * 416 ERR_TOOMANYMATCHES. Originated from IRCnet.
     * Returned when too many matches have been found for a command and the output has been truncated. An example would be the WHO command, where by the mask '*' would match everyone on the network! Ouch!
     * <command> [<mask>] :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanymatches(StdClass $line) {
        return $line;
    }*/

    /**
     * 416 ERR_QUERYTOOLONG. Multiple responses mapped. Originated from ircu.
     * Same as ERR_TOOMANYMATCHES
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_querytoolong(StdClass $line) {
        return $line;
    }*/

    /**
     * 419 ERR_LENGTHTRUNCATED. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_lengthtruncated(StdClass $line) {
        return $line;
    }*/

    /**
     * 421 ERR_UNKNOWNCOMMAND. Originated from RFC1459.
     * Returned when the given command is unknown to the server (or hidden because of lack of access rights)
     * <command> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_unknowncommand(StdClass $line) {
        return $line;
    }*/

    /**
     * 422 ERR_NOMOTD. Originated from RFC1459.
     * Sent when there is no MOTD to send the client
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nomotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 423 ERR_NOADMININFO. Originated from RFC1459.
     * Returned by a server in response to an ADMIN request when no information is available. RFC1459 mentions this in the list of numerics. While it's not listed as a valid reply in section 4.3.7 ('Admin command'), it's confirmed to exist in the real world.
     * <server> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noadmininfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 424 ERR_FILEERROR. Originated from RFC1459.
     * Generic error message used to report a failed file operation during the processing of a command
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_fileerror(StdClass $line) {
        return $line;
    }*/

    /**
     * 425 ERR_NOOPERMOTD. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noopermotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 429 ERR_TOOMANYAWAY. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanyaway(StdClass $line) {
        return $line;
    }*/

    /**
     * 430 ERR_EVENTNICKCHANGE. Originated from AustHex.
     * Returned by NICK when the user is not allowed to change their nickname due to a channel event (channel mode +E)
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_eventnickchange(StdClass $line) {
        return $line;
    }*/

    /**
     * 431 ERR_NONICKNAMEGIVEN. Could be used during registration. Originated from RFC1459.
     * Returned when a nickname parameter expected for a command isn't found
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nonicknamegiven(StdClass $line) {
        return $line;
    }*/

    /**
     * 432 ERR_ERRONEUSNICKNAME. Could be used during registration. Originated from RFC1459.
     * Returned after receiving a NICK message which contains a nickname which is considered invalid, such as it's reserved ('anonymous') or contains characters considered invalid for nicknames. This numeric is misspelt, but remains with this name for historical reasons :)
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_erroneusnickname(StdClass $line) {
        return $line;
    }*/

    /**
     * 433 ERR_NICKNAMEINUSE. Could be used during registration. Originated from RFC1459.
     * Returned by the NICK command when the given nickname is already in use
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nicknameinuse(StdClass $line) {
        return $line;
    }*/

    /**
     * 434 ERR_SERVICENAMEINUSE. Conflicting. Originated from AustHex?.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_servicenameinuse(StdClass $line) {
        return $line;
    }*/

    /**
     * 434 ERR_NORULES. Conflicting. Originated from Unreal, Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_norules(StdClass $line) {
        return $line;
    }*/

    /**
     * 435 ERR_SERVICECONFUSED. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_serviceconfused(StdClass $line) {
        return $line;
    }*/

    /**
     * 435 ERR_BANONCHAN. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_banonchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 436 ERR_NICKCOLLISION. Originated from RFC1459.
     * Returned by a server to a client when it detects a nickname collision
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nickcollision(StdClass $line) {
        return $line;
    }*/

    /**
     * 437 ERR_UNAVAILRESOURCE. Conflicting. Originated from RFC2812.
     * Return when the target is unable to be reached temporarily, eg. a delay mechanism in play, or a service being offline
     * <nick/channel/service> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_unavailresource(StdClass $line) {
        return $line;
    }*/

    /**
     * 437 ERR_BANNICKCHANGE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_bannickchange(StdClass $line) {
        return $line;
    }*/

    /**
     * 438 ERR_NICKTOOFAST. Conflicting. Originated from ircu.
     * Also known as ERR_NCHANGETOOFAST (Unreal, Ultimate)
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nicktoofast(StdClass $line) {
        return $line;
    }*/

    /**
     * 438 ERR_DEAD. Conflicting. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_dead(StdClass $line) {
        return $line;
    }*/

    /**
     * 439 ERR_TARGETTOOFAST. Originated from ircu.
     * Also known as many other things, RPL_INVTOOFAST, RPL_MSGTOOFAST etc
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_targettoofast(StdClass $line) {
        return $line;
    }*/

    /**
     * 440 ERR_SERVICESDOWN. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_servicesdown(StdClass $line) {
        return $line;
    }*/

    /**
     * 441 ERR_USERNOTINCHANNEL. Originated from RFC1459.
     * Returned by the server to indicate that the target user of the command is not on the given channel
     * <nick> <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_usernotinchannel(StdClass $line) {
        return $line;
    }*/

    /**
     * 442 ERR_NOTONCHANNEL. Originated from RFC1459.
     * Returned by the server whenever a client tries to perform a channel effecting command for which the client is not a member
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notonchannel(StdClass $line) {
        return $line;
    }*/

    /**
     * 443 ERR_USERONCHANNEL. Originated from RFC1459.
     * Returned when a client tries to invite a user to a channel they're already on
     * <nick> <channel> [:<reason>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_useronchannel(StdClass $line) {
        return $line;
    }*/

    /**
     * 444 ERR_NOLOGIN. Originated from RFC1459.
     * Returned by the SUMMON command if a given user was not logged in and could not be summoned
     * <user> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nologin(StdClass $line) {
        return $line;
    }*/

    /**
     * 445 ERR_SUMMONDISABLED. Originated from RFC1459.
     * Returned by SUMMON when it has been disabled or not implemented
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_summondisabled(StdClass $line) {
        return $line;
    }*/

    /**
     * 446 ERR_USERSDISABLED. Originated from RFC1459.
     * Returned by USERS when it has been disabled or not implemented
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_usersdisabled(StdClass $line) {
        return $line;
    }*/

    /**
     * 447 ERR_NONICKCHANGE. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nonickchange(StdClass $line) {
        return $line;
    }*/

    /**
     * 449 ERR_NOTIMPLEMENTED. Could be used during registration. Originated from Undernet.
     * Returned when a requested feature is not implemented (and cannot be completed)
     * Unspecified
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notimplemented(StdClass $line) {
        return $line;
    }*/

    /**
     * 451 ERR_NOTREGISTERED. Could be used during registration. Originated from RFC1459.
     * Returned by the server to indicate that the client must be registered before the server will allow it to be parsed in detail
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notregistered(StdClass $line) {
        return $line;
    }*/

    /**
     * 452 ERR_IDCOLLISION.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_idcollision(StdClass $line) {
        return $line;
    }*/

    /**
     * 453 ERR_NICKLOST.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nicklost(StdClass $line) {
        return $line;
    }*/

    /**
     * 455 ERR_HOSTILENAME. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_hostilename(StdClass $line) {
        return $line;
    }*/

    /**
     * 456 ERR_ACCEPTFULL.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_acceptfull(StdClass $line) {
        return $line;
    }*/

    /**
     * 457 ERR_ACCEPTEXIST.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_acceptexist(StdClass $line) {
        return $line;
    }*/

    /**
     * 458 ERR_ACCEPTNOT.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_acceptnot(StdClass $line) {
        return $line;
    }*/

    /**
     * 459 ERR_NOHIDING. Originated from Unreal.
     * Not allowed to become an invisible operator?
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nohiding(StdClass $line) {
        return $line;
    }*/

    /**
     * 460 ERR_NOTFORHALFOPS. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_notforhalfops(StdClass $line) {
        return $line;
    }*/

    /**
     * 461 ERR_NEEDMOREPARAMS. Could be used during registration. Originated from RFC1459.
     * Returned by the server by any command which requires more parameters than the number of parameters given
     * <command> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_needmoreparams(StdClass $line) {
        return $line;
    }*/

    /**
     * 462 ERR_ALREADYREGISTERED. Could be used during registration. Originated from RFC1459.
     * Returned by the server to any link which attempts to register again
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_alreadyregistered(StdClass $line) {
        return $line;
    }*/

    /**
     * 463 ERR_NOPERMFORHOST. Originated from RFC1459.
     * Returned to a client which attempts to register with a server which has been configured to refuse connections from the client's host
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nopermforhost(StdClass $line) {
        return $line;
    }*/

    /**
     * 464 ERR_PASSWDMISMATCH. Could be used during registration. Originated from RFC1459.
     * Returned by the PASS command to indicate the given password was required and was either not given or was incorrect
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_passwdmismatch(StdClass $line) {
        return $line;
    }*/

    /**
     * 465 ERR_YOUREBANNEDCREEP. Originated from RFC1459.
     * Returned to a client after an attempt to register on a server configured to ban connections from that client
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_yourebannedcreep(StdClass $line) {
        return $line;
    }*/

    /**
     * 466 ERR_YOUWILLBEBANNED. Obsolete. Originated from RFC1459.
     * Sent by a server to a user to inform that access to the server will soon be denied
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_youwillbebanned(StdClass $line) {
        return $line;
    }*/

    /**
     * 467 ERR_KEYSET. Originated from RFC1459.
     * Returned when the channel key for a channel has already been set
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_keyset(StdClass $line) {
        return $line;
    }*/

    /**
     * 468 ERR_INVALIDUSERNAME. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_invalidusername(StdClass $line) {
        return $line;
    }*/

    /**
     * 468 ERR_ONLYSERVERSCANCHANGE. Conflicting. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_onlyserverscanchange(StdClass $line) {
        return $line;
    }*/

    /**
     * 469 ERR_LINKSET. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_linkset(StdClass $line) {
        return $line;
    }*/

    /**
     * 470 ERR_LINKCHANNEL. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_linkchannel(StdClass $line) {
        return $line;
    }*/

    /**
     * 470 ERR_KICKEDFROMCHAN. Conflicting. Originated from aircd.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_kickedfromchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 471 ERR_CHANNELISFULL. Originated from RFC1459.
     * Returned when attempting to join a channel which is set +l and is already full
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_channelisfull(StdClass $line) {
        return $line;
    }*/

    /**
     * 472 ERR_UNKNOWNMODE. Originated from RFC1459.
     * Returned when a given mode is unknown
     * <char> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_unknownmode(StdClass $line) {
        return $line;
    }*/

    /**
     * 473 ERR_INVITEONLYCHAN. Originated from RFC1459.
     * Returned when attempting to join a channel which is invite only without an invitation
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_inviteonlychan(StdClass $line) {
        return $line;
    }*/

    /**
     * 474 ERR_BANNEDFROMCHAN. Originated from RFC1459.
     * Returned when attempting to join a channel a user is banned from
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_bannedfromchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 475 ERR_BADCHANNELKEY. Originated from RFC1459.
     * Returned when attempting to join a key-locked channel either without a key or with the wrong key
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badchannelkey(StdClass $line) {
        return $line;
    }*/

    /**
     * 476 ERR_BADCHANMASK. Originated from RFC2812.
     * The given channel mask was invalid
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badchanmask(StdClass $line) {
        return $line;
    }*/

    /**
     * 477 ERR_NOCHANMODES. Conflicting. Originated from RFC2812.
     * Returned when attempting to set a mode on a channel which does not support channel modes, or channel mode changes. Also known as ERR_MODELESS
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nochanmodes(StdClass $line) {
        return $line;
    }*/

    /**
     * 477 ERR_NEEDREGGEDNICK. Conflicting. Originated from Bahamut, ircu, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_needreggednick(StdClass $line) {
        return $line;
    }*/

    /**
     * 478 ERR_BANLISTFULL. Originated from RFC2812.
     * Returned when a channel access list (i.e. ban list etc) is full and cannot be added to
     * <channel> <char> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_banlistfull(StdClass $line) {
        return $line;
    }*/

    /**
     * 479 ERR_BADCHANNAME. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badchanname(StdClass $line) {
        return $line;
    }*/

    /**
     * 479 ERR_LINKFAIL. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_linkfail(StdClass $line) {
        return $line;
    }*/

    /**
     * 480 ERR_NOULINE. Conflicting. Originated from AustHex.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nouline(StdClass $line) {
        return $line;
    }*/

    /**
     * 480 ERR_CANNOTKNOCK. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotknock(StdClass $line) {
        return $line;
    }*/

    /**
     * 481 ERR_NOPRIVILEGES. Originated from RFC1459.
     * Returned by any command requiring special privileges (eg. IRC operator) to indicate the operation was unsuccessful
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noprivileges(StdClass $line) {
        return $line;
    }*/

    /**
     * 482 ERR_CHANOPRIVSNEEDED. Originated from RFC1459.
     * Returned by any command requiring special channel privileges (eg. channel operator) to indicate the operation was unsuccessful
     * <channel> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_chanoprivsneeded(StdClass $line) {
        return $line;
    }*/

    /**
     * 483 ERR_CANTKILLSERVER. Originated from RFC1459.
     * Returned by KILL to anyone who tries to kill a server
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cantkillserver(StdClass $line) {
        return $line;
    }*/

    /**
     * 484 ERR_RESTRICTED. Conflicting. Originated from RFC2812.
     * Sent by the server to a user upon connection to indicate the restricted nature of the connection (i.e. usermode +r)
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_restricted(StdClass $line) {
        return $line;
    }*/

    /**
     * 484 ERR_ISCHANSERVICE. Conflicting. Originated from Undernet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_ischanservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 484 ERR_DESYNC. Conflicting. Originated from Bahamut, Hybrid, PTlink.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_desync(StdClass $line) {
        return $line;
    }*/

    /**
     * 484 ERR_ATTACKDENY. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_attackdeny(StdClass $line) {
        return $line;
    }*/

    /**
     * 485 ERR_UNIQOPRIVSNEEDED. Originated from RFC2812.
     * Any mode requiring 'channel creator' privileges returns this error if the client is attempting to use it while not a channel creator on the given channel
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_uniqoprivsneeded(StdClass $line) {
        return $line;
    }*/

    /**
     * 485 ERR_KILLDENY. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_killdeny(StdClass $line) {
        return $line;
    }*/

    /**
     * 485 ERR_CANTKICKADMIN. Conflicting. Originated from PTlink.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cantkickadmin(StdClass $line) {
        return $line;
    }*/

    /**
     * 485 ERR_ISREALSERVICE. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_isrealservice(StdClass $line) {
        return $line;
    }*/

    /**
     * 486 ERR_NONONREG. Conflicting.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nononreg(StdClass $line) {
        return $line;
    }*/

    /**
     * 486 ERR_HTMDISABLED. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_htmdisabled(StdClass $line) {
        return $line;
    }*/

    /**
     * 486 ERR_ACCOUNTONLY. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_accountonly(StdClass $line) {
        return $line;
    }*/

    /**
     * 487 ERR_CHANTOORECENT. Conflicting. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_chantoorecent(StdClass $line) {
        return $line;
    }*/

    /**
     * 487 ERR_MSGSERVICES. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_msgservices(StdClass $line) {
        return $line;
    }*/

    /**
     * 488 ERR_TSLESSCHAN. Originated from IRCnet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_tslesschan(StdClass $line) {
        return $line;
    }*/

    /**
     * 489 ERR_VOICENEEDED. Conflicting. Originated from Undernet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_voiceneeded(StdClass $line) {
        return $line;
    }*/

    /**
     * 489 ERR_SECUREONLYCHAN. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_secureonlychan(StdClass $line) {
        return $line;
    }*/

    /**
     * 491 ERR_NOOPERHOST. Originated from RFC1459.
     * Returned by OPER to a client who cannot become an IRC operator because the server has been configured to disallow the client's host
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nooperhost(StdClass $line) {
        return $line;
    }*/

    /**
     * 492 ERR_NOSERVICEHOST. Obsolete. Originated from RFC1459.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noservicehost(StdClass $line) {
        return $line;
    }*/

    /**
     * 493 ERR_NOFEATURE. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nofeature(StdClass $line) {
        return $line;
    }*/

    /**
     * 494 ERR_BADFEATURE. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badfeature(StdClass $line) {
        return $line;
    }*/

    /**
     * 495 ERR_BADLOGTYPE. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badlogtype(StdClass $line) {
        return $line;
    }*/

    /**
     * 496 ERR_BADLOGSYS. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badlogsys(StdClass $line) {
        return $line;
    }*/

    /**
     * 497 ERR_BADLOGVALUE. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badlogvalue(StdClass $line) {
        return $line;
    }*/

    /**
     * 498 ERR_ISOPERLCHAN. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_isoperlchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 499 ERR_CHANOWNPRIVNEEDED. Originated from Unreal.
     * See also command 482.
     * Works just like ERR_CHANOPRIVSNEEDED except it indicates that owner status (+q) is needed.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_chanownprivneeded(StdClass $line) {
        return $line;
    }*/

    /**
     * 501 ERR_UMODEUNKNOWNFLAG. Originated from RFC1459.
     * Returned by the server to indicate that a MODE message was sent with a nickname parameter and that the mode flag sent was not recognised
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_umodeunknownflag(StdClass $line) {
        return $line;
    }*/

    /**
     * 502 ERR_USERSDONTMATCH. Originated from RFC1459.
     * Error sent to any user trying to view or change the user mode for a user other than themselves
     * :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_usersdontmatch(StdClass $line) {
        return $line;
    }*/

    /**
     * 503 ERR_GHOSTEDCLIENT. Originated from Hybrid.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_ghostedclient(StdClass $line) {
        return $line;
    }*/

    /**
     * 503 ERR_VWORLDWARN. Obsolete. Originated from AustHex.
     * See also command 662.
     * Warning about Virtual-World being turned off. Obsoleted in favour for RPL_MODECHANGEWARN
     * :<warning_text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_vworldwarn(StdClass $line) {
        return $line;
    }*/

    /**
     * 504 ERR_USERNOTONSERV.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_usernotonserv(StdClass $line) {
        return $line;
    }*/

    /**
     * 511 ERR_SILELISTFULL. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_silelistfull(StdClass $line) {
        return $line;
    }*/

    /**
     * 512 ERR_TOOMANYWATCH. Originated from Bahamut.
     * Also known as ERR_NOTIFYFULL (aircd), I presume they are the same
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanywatch(StdClass $line) {
        return $line;
    }*/

    /**
     * 513 ERR_BADPING. Could be used during registration. Originated from ircu.
     * Also known as ERR_NEEDPONG (Unreal/Ultimate) for use during registration, however it's not used in Unreal (and might not be used in Ultimate either).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badping(StdClass $line) {
        return $line;
    }*/

    /**
     * 514 ERR_INVALID_ERROR. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_invalid_error(StdClass $line) {
        return $line;
    }*/

    /**
     * 514 ERR_TOOMANYDCC. Conflicting. Originated from Bahamut (+ Unreal?).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanydcc(StdClass $line) {
        return $line;
    }*/

    /**
     * 515 ERR_BADEXPIRE. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badexpire(StdClass $line) {
        return $line;
    }*/

    /**
     * 516 ERR_DONTCHEAT. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_dontcheat(StdClass $line) {
        return $line;
    }*/

    /**
     * 517 ERR_DISABLED. Originated from ircu.
     * <command> :<info/reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_disabled(StdClass $line) {
        return $line;
    }*/

    /**
     * 518 ERR_NOINVITE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noinvite(StdClass $line) {
        return $line;
    }*/

    /**
     * 518 ERR_LONGMASK. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_longmask(StdClass $line) {
        return $line;
    }*/

    /**
     * 519 ERR_ADMONLY. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_admonly(StdClass $line) {
        return $line;
    }*/

    /**
     * 519 ERR_TOOMANYUSERS. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanyusers(StdClass $line) {
        return $line;
    }*/

    /**
     * 520 ERR_OPERONLY. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_operonly(StdClass $line) {
        return $line;
    }*/

    /**
     * 520 ERR_MASKTOOWIDE. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_masktoowide(StdClass $line) {
        return $line;
    }*/

    /**
     * 520 ERR_WHOTRUNC. Obsolete. Originated from AustHex.
     * See also command 416.
     * This is considered obsolete in favour of ERR_TOOMANYMATCHES, and should no longer be used.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_whotrunc(StdClass $line) {
        return $line;
    }*/

    /**
     * 521 ERR_LISTSYNTAX. Conflicting. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_listsyntax(StdClass $line) {
        return $line;
    }*/

    /**
     * 522 ERR_WHOSYNTAX. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_whosyntax(StdClass $line) {
        return $line;
    }*/

    /**
     * 523 ERR_WHOLIMEXCEED. Originated from Bahamut.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_wholimexceed(StdClass $line) {
        return $line;
    }*/

    /**
     * 524 ERR_QUARANTINED. Conflicting. Originated from ircu.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_quarantined(StdClass $line) {
        return $line;
    }*/

    /**
     * 524 ERR_OPERSPVERIFY. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_operspverify(StdClass $line) {
        return $line;
    }*/

    /**
     * 525 ERR_REMOTEPFX. Originated from CAPAB USERCMDPFX.
     * More Info http://www.hades.skumler.net/~ejb/draft-brocklesby-irc-usercmdpfx-00.txt
     * Proposed.
     * <nickname> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_remotepfx(StdClass $line) {
        return $line;
    }*/

    /**
     * 526 ERR_PFXUNROUTABLE. Originated from CAPAB USERCMDPFX.
     * More Info http://www.hades.skumler.net/~ejb/draft-brocklesby-irc-usercmdpfx-00.txt
     * Proposed.
     * <nickname> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_pfxunroutable(StdClass $line) {
        return $line;
    }*/

    /**
     * 550 ERR_BADHOSTMASK. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badhostmask(StdClass $line) {
        return $line;
    }*/

    /**
     * 551 ERR_HOSTUNAVAIL. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_hostunavail(StdClass $line) {
        return $line;
    }*/

    /**
     * 552 ERR_USINGSLINE. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_usingsline(StdClass $line) {
        return $line;
    }*/

    /**
     * 553 ERR_STATSSLINE. Conflicting. Originated from QuakeNet.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_statssline(StdClass $line) {
        return $line;
    }*/

    /*
     * Section 600-899, reply from server commands
     */

    /**
     * 600 RPL_LOGON. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_logon(StdClass $line) {
        return $line;
    }*/

    /**
     * 601 RPL_LOGOFF. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_logoff(StdClass $line) {
        return $line;
    }*/

    /**
     * 602 RPL_WATCHOFF. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_watchoff(StdClass $line) {
        return $line;
    }*/

    /**
     * 603 RPL_WATCHSTAT. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_watchstat(StdClass $line) {
        return $line;
    }*/

    /**
     * 604 RPL_NOWON. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nowon(StdClass $line) {
        return $line;
    }*/

    /**
     * 605 RPL_NOWOFF. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_nowoff(StdClass $line) {
        return $line;
    }*/

    /**
     * 606 RPL_WATCHLIST. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_watchlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 607 RPL_ENDOFWATCHLIST. Originated from Bahamut, Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofwatchlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 608 RPL_WATCHCLEAR. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_watchclear(StdClass $line) {
        return $line;
    }*/

    /**
     * 610 RPL_MAPMORE. Conflicting. Originated from Unreal.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapmore(StdClass $line) {
        return $line;
    }*/

    /**
     * 610 RPL_ISOPER. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_isoper(StdClass $line) {
        return $line;
    }*/

    /**
     * 611 RPL_ISLOCOP. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_islocop(StdClass $line) {
        return $line;
    }*/

    /**
     * 612 RPL_ISNOTOPER. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_isnotoper(StdClass $line) {
        return $line;
    }*/

    /**
     * 613 RPL_ENDOFISOPER. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofisoper(StdClass $line) {
        return $line;
    }*/

    /**
     * 615 RPL_MAPMORE. Conflicting. Originated from PTlink.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapmore(StdClass $line) {
        return $line;
    }*/

    /**
     * 615 RPL_WHOISMODES. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoismodes(StdClass $line) {
        return $line;
    }*/

    /**
     * 616 RPL_WHOISHOST. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoishost(StdClass $line) {
        return $line;
    }*/

    /**
     * 617 RPL_DCCSTATUS. Conflicting. Originated from Bahamut ( + Unreal?).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_dccstatus(StdClass $line) {
        return $line;
    }*/

    /**
     * 617 RPL_WHOISBOT. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisbot(StdClass $line) {
        return $line;
    }*/

    /**
     * 618 RPL_DCCLIST. Originated from Bahamut (+ Unreal?).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_dcclist(StdClass $line) {
        return $line;
    }*/

    /**
     * 619 RPL_ENDOFDCCLIST. Conflicting. Originated from Bahamut (+ Unreal?).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofdcclist(StdClass $line) {
        return $line;
    }*/

    /**
     * 619 RPL_WHOWASHOST. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whowashost(StdClass $line) {
        return $line;
    }*/

    /**
     * 620 RPL_DCCINFO. Conflicting. Originated from Bahamut (+ Unreal?).
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_dccinfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 620 RPL_RULESSTART. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_rulesstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 621 RPL_RULES. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_rules(StdClass $line) {
        return $line;
    }*/

    /**
     * 622 RPL_ENDOFRULES. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofrules(StdClass $line) {
        return $line;
    }*/

    /**
     * 623 RPL_MAPMORE. Conflicting. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_mapmore(StdClass $line) {
        return $line;
    }*/

    /**
     * 624 RPL_OMOTDSTART. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_omotdstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 625 RPL_OMOTD. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_omotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 626 RPL_ENDOFOMOTD. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofomotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 630 RPL_SETTINGS. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_settings(StdClass $line) {
        return $line;
    }*/

    /**
     * 631 RPL_ENDOFSETTINGS. Originated from Ultimate.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofsettings(StdClass $line) {
        return $line;
    }*/

    /**
     * 640 RPL_DUMPING. Obsolete. Originated from Unreal.
     * Never actually used by Unreal - was defined however the feature that would have used this numeric was never created.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_dumping(StdClass $line) {
        return $line;
    }*/

    /**
     * 641 RPL_DUMPRPL. Obsolete. Originated from Unreal.
     * Never actually used by Unreal - was defined however the feature that would have used this numeric was never created.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_dumprpl(StdClass $line) {
        return $line;
    }*/

    /**
     * 642 RPL_EODUMP. Obsolete. Originated from Unreal.
     * Never actually used by Unreal - was defined however the feature that would have used this numeric was never created.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_eodump(StdClass $line) {
        return $line;
    }*/

    /**
     * 660 RPL_TRACEROUTE_HOP. Originated from KineIRCd.
     * Returned from the TRACEROUTE IRC-Op command when tracerouting a host
     * <target> <hop#> [<address> [<hostname> | '*'] <usec_ping>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceroute_hop(StdClass $line) {
        return $line;
    }*/

    /**
     * 661 RPL_TRACEROUTE_START. Originated from KineIRCd.
     * Start of an RPL_TRACEROUTE_HOP list
     * <target> <target_FQDN> <target_address> <max_hops>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_traceroute_start(StdClass $line) {
        return $line;
    }*/

    /**
     * 662 RPL_MODECHANGEWARN. Originated from KineIRCd.
     * Plain text warning to the user about turning on or off a user mode. If no '+' or '-' prefix is used for the mode char, '+' is presumed.
     * ['+' | '-']<mode_char> :<warning>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_modechangewarn(StdClass $line) {
        return $line;
    }*/

    /**
     * 663 RPL_CHANREDIR. Originated from KineIRCd.
     * Used to notify the client upon JOIN that they are joining a different channel than expected because the IRC Daemon has been set up to map the channel they attempted to join to the channel they eventually will join.
     * <old_chan> <new_chan> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_chanredir(StdClass $line) {
        return $line;
    }*/

    /**
     * 664 RPL_SERVMODEIS. Originated from KineIRCd.
     * Reply to MODE <servername>. KineIRCd supports server modes to simplify configuration of servers; Similar to RPL_CHANNELMODEIS
     * <server> <modes> <parameters>..
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_servmodeis(StdClass $line) {
        return $line;
    }*/

    /**
     * 665 RPL_OTHERUMODEIS. Originated from KineIRCd.
     * Reply to MODE <nickname> to return the user-modes of another user to help troubleshoot connections, etc. Similar to RPL_UMODEIS, however including the target
     * <nickname> <modes>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_otherumodeis(StdClass $line) {
        return $line;
    }*/

    /**
     * 666 RPL_ENDOF_GENERIC. Originated from KineIRCd.
     * Generic response for new lists to save numerics.
     * <command> [<parameter> ...] :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endof_generic(StdClass $line) {
        return $line;
    }*/

    /**
     * 670 RPL_WHOWASDETAILS. Originated from KineIRCd.
     * Returned by WHOWAS to return extended information (if available). The type field is a number indication what kind of information.
     * <nick> <type> :<information>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whowasdetails(StdClass $line) {
        return $line;
    }*/

    /**
     * 671 RPL_WHOISSECURE. Originated from KineIRCd.
     * Reply to WHOIS command - Returned if the target is connected securely, eg. type may be TLSv1, or SSLv2 etc. If the type is unknown, a '*' may be used.
     * <nick> <type> [:<info>]
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoissecure(StdClass $line) {
        return $line;
    }*/

    /**
     * 672 RPL_UNKNOWNMODES. Originated from Ithildin.
     * Returns a full list of modes that are unknown when a client issues a MODE command (rather than one numeric per mode)
     * <modes> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_unknownmodes(StdClass $line) {
        return $line;
    }*/

    /**
     * 673 RPL_CANNOTSETMODES. Originated from Ithildin.
     * Returns a full list of modes that cannot be set when a client issues a MODE command
     * <modes> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_cannotsetmodes(StdClass $line) {
        return $line;
    }*/

    /**
     * 678 RPL_LUSERSTAFF. Originated from KineIRCd.
     * Reply to LUSERS command - Number of network staff (or 'helpers') online (differs from Local/Global operators). Similar format to RPL_LUSEROP
     * <staff_online_count> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_luserstaff(StdClass $line) {
        return $line;
    }*/

    /**
     * 679 RPL_TIMEONSERVERIS. Originated from KineIRCd.
     * Optionally sent upon connection, and/or sent as a reply to the TIME command. This returns the time on the server in a uniform manner. The seconds (and optionally nanoseconds) is the time since the UNIX Epoch, and is used since many existing timestamps in the IRC-2 protocol are done this way (i.e. ban lists). The timezone is hours and minutes each of Greenwich ('[+/-]HHMM'). Since all timestamps sent from the server are in a similar format, this numeric is designed to give clients the ability to provide accurate timestamps to their users.
     * <seconds> [<nanoseconds> | '0'] <timezone> <flags> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_timeonserveris(StdClass $line) {
        return $line;
    }*/

    /**
     * 682 RPL_NETWORKS. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/IIRC?rev=HEAD
     * A reply to the NETWORKS command when requesting a list of known networks (within the IIRC domain).
     * <name> <through_name> <hops> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_networks(StdClass $line) {
        return $line;
    }*/

    /**
     * 687 RPL_YOURLANGUAGEIS. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD
     * Reply to the LANGUAGE command, informing the client of the language(s) it has set
     * <code(s)> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_yourlanguageis(StdClass $line) {
        return $line;
    }*/

    /**
     * 688 RPL_LANGUAGE. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD
     * A language reply to LANGUAGE when requesting a list of known languages
     * <code> <revision> <maintainer> <flags> * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_language(StdClass $line) {
        return $line;
    }*/

    /**
     * 689 RPL_WHOISSTAFF. Originated from KineIRCd.
     * The user is a staff member. The information may explain the user's job role, or simply state that they are a part of the network staff. Staff members are not IRC operators, but rather people who have special access in association with network services. KineIRCd uses this numeric instead of the existing numerics due to the overwhelming number of conflicts.
     * :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoisstaff(StdClass $line) {
        return $line;
    }*/

    /**
     * 690 RPL_WHOISLANGUAGE. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD
     * Reply to WHOIS command - A list of languages someone can speak. The language codes are comma delimitered.
     * <nick> <language codes>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_whoislanguage(StdClass $line) {
        return $line;
    }*/

    /**
     * 702 RPL_MODLIST. Originated from RatBox.
     * Output from the MODLIST command
     * <?> 0x<?> <?> <?>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_modlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 703 RPL_ENDOFMODLIST. Originated from RatBox.
     * Terminates MODLIST output
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofmodlist(StdClass $line) {
        return $line;
    }*/

    /**
     * 704 RPL_HELPSTART. Originated from RatBox.
     * Start of HELP command output
     * <command> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helpstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 705 RPL_HELPTXT. Originated from RatBox.
     * Output from HELP command
     * <command> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_helptxt(StdClass $line) {
        return $line;
    }*/

    /**
     * 706 RPL_ENDOFHELP. Originated from RatBox.
     * End of HELP command output
     * <command> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofhelp(StdClass $line) {
        return $line;
    }*/

    /**
     * 708 RPL_ETRACEFULL. Originated from RatBox.
     * Output from 'extended' trace
     * <?> <?> <?> <?> <?> <?> <?> :<?>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_etracefull(StdClass $line) {
        return $line;
    }*/

    /**
     * 709 RPL_ETRACE. Originated from RatBox.
     * Output from 'extended' trace
     * <?> <?> <?> <?> <?> <?> :<?>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_etrace(StdClass $line) {
        return $line;
    }*/

    /**
     * 710 RPL_KNOCK. Originated from RatBox.
     * Message delivered using KNOCK command
     * <channel> <nick>!<user>@<host> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_knock(StdClass $line) {
        return $line;
    }*/

    /**
     * 711 RPL_KNOCKDLVR. Originated from RatBox.
     * Message returned from using KNOCK command
     * <channel> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_knockdlvr(StdClass $line) {
        return $line;
    }*/

    /**
     * 712 ERR_TOOMANYKNOCK. Originated from RatBox.
     * Message returned when too many KNOCKs for a channel have been sent by a user
     * <channel> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanyknock(StdClass $line) {
        return $line;
    }*/

    /**
     * 713 ERR_CHANOPEN. Originated from RatBox.
     * Message returned from KNOCK when the channel can be freely joined by the user
     * <channel> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_chanopen(StdClass $line) {
        return $line;
    }*/

    /**
     * 714 ERR_KNOCKONCHAN. Originated from RatBox.
     * Message returned from KNOCK when the user has used KNOCK on a channel they have already joined
     * <channel> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_knockonchan(StdClass $line) {
        return $line;
    }*/

    /**
     * 715 ERR_KNOCKDISABLED. Originated from RatBox.
     * Returned from KNOCK when the command has been disabled
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_knockdisabled(StdClass $line) {
        return $line;
    }*/

    /**
     * 716 RPL_TARGUMODEG. Originated from RatBox.
     * Sent to indicate the given target is set +g (server-side ignore)
     * <nick> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_targumodeg(StdClass $line) {
        return $line;
    }*/

    /**
     * 717 RPL_TARGNOTIFY. Originated from RatBox.
     * Sent following a PRIVMSG/NOTICE to indicate the target has been notified of an attempt to talk to them while they are set +g
     * <nick> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_targnotify(StdClass $line) {
        return $line;
    }*/

    /**
     * 718 RPL_UMODEGMSG. Originated from RatBox.
     * Sent to a user who is +g to inform them that someone has attempted to talk to them (via PRIVMSG/NOTICE), and that they will need to be accepted (via the ACCEPT command) before being able to talk to them
     * <nick> <user>@<host> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_umodegmsg(StdClass $line) {
        return $line;
    }*/

    /**
     * 720 RPL_OMOTDSTART. Originated from RatBox.
     * IRC Operator MOTD header, sent upon OPER command
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_omotdstart(StdClass $line) {
        return $line;
    }*/

    /**
     * 721 RPL_OMOTD. Originated from RatBox.
     * IRC Operator MOTD text (repeated, usually)
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_omotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 722 RPL_ENDOFOMOTD. Originated from RatBox.
     * IRC operator MOTD footer
     * :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_endofomotd(StdClass $line) {
        return $line;
    }*/

    /**
     * 723 ERR_NOPRIVS. Originated from RatBox.
     * Returned from an oper command when the IRC operator does not have the relevant operator privileges.
     * <command> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_noprivs(StdClass $line) {
        return $line;
    }*/

    /**
     * 724 RPL_TESTMARK. Originated from RatBox.
     * Reply from an oper command reporting how many users match a given user@host mask
     * <nick>!<user>@<host> <?> <?> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_testmark(StdClass $line) {
        return $line;
    }*/

    /**
     * 725 RPL_TESTLINE. Originated from RatBox.
     * Reply from an oper command reporting relevant I/K lines that will match a given user@host
     * <?> <?> <?> :<?>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_testline(StdClass $line) {
        return $line;
    }*/

    /**
     * 726 RPL_NOTESTLINE. Originated from RatBox.
     * Reply from oper command reporting no I/K lines match the given user@host
     * <?> :<text>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_notestline(StdClass $line) {
        return $line;
    }*/

    /**
     * 740 RPL_CHALLENGE_TEXT. Originated from RatBox.
     * Displays CHALLENGE text
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_challenge_text(StdClass $line) {
        return $line;
    }*/

    /**
     * 741 RPL_CHALLENGE_END. Originated from RatBox.
     * End of CHALLENGE numeric
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_challenge_end(StdClass $line) {
        return $line;
    }*/

    /**
     * 771 RPL_XINFO. Originated from Ithildin.
     * Used to send 'eXtended info' to the client, a replacement for the STATS command to send a large variety of data and minimise numeric pollution.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_xinfo(StdClass $line) {
        return $line;
    }*/

    /**
     * 773 RPL_XINFOSTART. Originated from Ithildin.
     * Start of an RPL_XINFO list
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_xinfostart(StdClass $line) {
        return $line;
    }*/

    /**
     * 774 RPL_XINFOEND. Originated from Ithildin.
     * Termination of an RPL_XINFO list
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_xinfoend(StdClass $line) {
        return $line;
    }*/

    /*
     * Section 900-999, errors (usually)
     */

    /**
     * 903 RPL_SASL. Originated from charybdis.
     * Authentication via SASL successful.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_rpl_sasl(StdClass $line) {
        return $line;
    }*/

    /**
     * 904 ERR_SASL. Originated from charybdis.
     * Authentication via SASL unsuccessful.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_sasl(StdClass $line) {
        return $line;
    }*/

    /**
     * 972 ERR_CANNOTDOCOMMAND. Originated from Unreal.
     * Works similarly to all of KineIRCd's CANNOT* numerics. This one indicates that a command could not be performed for an arbitrary reason. For example, a halfop trying to kick an op.
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotdocommand(StdClass $line) {
        return $line;
    }*/

    /**
     * 973 ERR_CANNOTCHANGEUMODE. Originated from KineIRCd.
     * Reply to MODE when a user cannot change a user mode
     * <mode_char> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotchangeumode(StdClass $line) {
        return $line;
    }*/

    /**
     * 974 ERR_CANNOTCHANGECHANMODE. Originated from KineIRCd (+ Unreal?).
     * Reply to MODE when a user cannot change a channel mode
     * <mode_char> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotchangechanmode(StdClass $line) {
        return $line;
    }*/

    /**
     * 975 ERR_CANNOTCHANGESERVERMODE. Originated from KineIRCd.
     * Reply to MODE when a user cannot change a server mode
     * <mode_char> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotchangeservermode(StdClass $line) {
        return $line;
    }*/

    /**
     * 976 ERR_CANNOTSENDTONICK. Originated from KineIRCd.
     * Returned from NOTICE, PRIVMSG or other commands to notify the user that they cannot send a message to a particular client. Similar to ERR_CANNOTSENDTOCHAN. KineIRCd uses this in conjunction with user-mode +R to allow users to block people who are not identified to services (spam avoidance)
     * <nick> :<reason>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_cannotsendtonick(StdClass $line) {
        return $line;
    }*/

    /**
     * 977 ERR_UNKNOWNSERVERMODE. Originated from KineIRCd.
     * Returned by MODE to inform the client they used an unknown server mode character.
     * <modechar> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_unknownservermode(StdClass $line) {
        return $line;
    }*/

    /**
     * 979 ERR_SERVERMODELOCK. Originated from KineIRCd.
     * Returned by MODE to inform the client the server has been set mode +L by an administrator to stop server modes being changed
     * <target> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_servermodelock(StdClass $line) {
        return $line;
    }*/

    /**
     * 980 ERR_BADCHARENCODING. Originated from KineIRCd.
     * Returned by any command which may have had the given data modified because one or more glyphs were incorrectly encoded in the current charset (given). Such a use would be where an invalid UTF-8 sequence was given which may be considered insecure, or defines a character which is invalid within that context. For safety reasons, the invalid character is not returned to the client.
     * <command> <charset> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_badcharencoding(StdClass $line) {
        return $line;
    }*/

    /**
     * 981 ERR_TOOMANYLANGUAGES. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD
     * Returned by the LANGUAGE command to tell the client they cannot set as many languages as they have requested. To assist the client, the maximum languages which can be set at one time is given, and the language settings are not changed.
     * <max_langs> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_toomanylanguages(StdClass $line) {
        return $line;
    }*/

    /**
     * 982 ERR_NOLANGUAGE. Originated from KineIRCd.
     * More Info http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD
     * Returned by the LANGUAGE command to tell the client it has specified an unknown language code.
     * <language_code> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_nolanguage(StdClass $line) {
        return $line;
    }*/

    /**
     * 983 ERR_TEXTTOOSHORT. Originated from KineIRCd.
     * Returned by any command requiring text (such as a message or a reason), which was not long enough to be considered valid. This was created initially to combat '/wallops foo' abuse, but is also used by DIE and RESTART commands to attempt to encourage meaningful reasons.
     * <command> :<info>
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_texttooshort(StdClass $line) {
        return $line;
    }*/

    /**
     * 999 ERR_NUMERIC_ERR. Originated from Bahamut.
     * Also known as ERR_NUMERICERR (Unreal)
     *
     * @param  StdClass $line A partially pre-parsed IRC protocol line
     *
     * @return StdClass       A StdClass that has additional information parsed, if available.
     */
    /*private function parse_err_numeric_err(StdClass $line) {
        return $line;
    }*/
}