<?php
/*
 * This file was originally taken from here:
 * https://www.alien.net.au/irc/irc2numerics.html
 * https://www.alien.net.au/irc/irc2numerics.def
 * With tag:
 * $Id: irc2numerics.def,v 1.50 2005/01/11 22:30:30 pickle Exp $
 *
 * Original format is Copyright (c) 2001,2002,2003,2004 Simon Butcher
 *  <pickle@alien.net.au>
 *
 * ******
 * Modifications:
 * Converted to a PHP array syntax by A.B. Carroll <ben@hl9.net>
 * ******
 *
 * Original Header:
 * This file is PUBLIC DOMAIN, to benefit the IRC developer community. If you
 * modify this file, please state your name and modifications here in order
 * for people to be able to distinguish between your version and this version.
 */

/* The following format is used through-out this file. The first two fields
 * ('name' and 'numeric') are manditory, the others may or may not exist.
 *
 * The registration field is only used to determine if the numeric is
 * *POSSIBLY* used during registration of a connection (before the main part
 * of the protocol kicks in).
 *
 * irc2numerics = {
 *    name         = "<numeric name>";
 *    numeric      = "<number>";
 *  [ origin       = "<where the numeric was found>"; ]
 *  [ when	   = "<release version or announced date>"; ]
 *  [ contact      = "<point of contact associated with the numeric>"; ]
 *  [ information  = "<url where to find more information>"; ]
 *  [ format       = "<format of the numeric data>"; ]
 *  [ comment      = "<comments, history etc>"; ]
 *  [ seealso      = "<number>"; ]
 *  [ registration = "yes"; ]
 *  [ conflict     = "yes"; ]
 *  [ obsolete     = "yes"; ]
 *  [ repeated     = "yes"; ]
 * }
 */




/* EDIT THIS */
// 'variables' or 'functions'
$format = 'variables';
/* EDIT THIS */


$irc2numerics[] = "000-199, local server to client connections";

$irc2numerics[] = [
    "name"    => "RPL_WELCOME",
    "numeric" => "1",
    "origin"  => "RFC2812",
    "format"  => ":Welcome to the Internet Relay Network "
        . "<nick>!<user>@<host>",
    "comment" => "The first message sent after client registration. The text "
        . "used varies widely",
];

$irc2numerics[] = [
    "name"    => "RPL_YOURHOST",
    "numeric" => "2",
    "origin"  => "RFC2812",
    "format"  => ":Your host is <servername>, running version <version>",
    "comment" => "Part of the post-registration greeting. Text varies widely",
];

$irc2numerics[] = [
    "name"    => "RPL_CREATED",
    "numeric" => "3",
    "origin"  => "RFC2812",
    "format"  => ":This server was created <date>",
    "comment" => "Part of the post-registration greeting. Text varies widely",
];

$irc2numerics[] = [
    "name"    => "RPL_MYINFO",
    "numeric" => "4",
    "origin"  => "RFC2812",
    "format"  => "<server_name> <version> <user_modes> <chan_modes>",
    "comment" => "Part of the post-registration greeting",
];

$irc2numerics[] = [
    "name"     => "RPL_MYINFO",
    "numeric"  => "4",
    "origin"   => "KineIRCd",
    "contact"  => "kineircd@alien.net.au",
    "format"   => "<server_name> <version> <user_modes> <chan_modes> "
        . "<channel_modes_with_params> <user_modes_with_params> "
        . "<server_modes> <server_modes_with_params>",
    "comment"  => "Same as RFC2812 however with additional fields to avoid "
        . "additional 005 burden.",
    "repeated" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_BOUNCE",
    "numeric"  => "5",
    "origin"   => "RFC2812",
    "format"   => ":Try server <server_name>, port <port_number>",
    "comment"  => "Sent by the server to a user to suggest an alternative "
        . "server, sometimes used when the connection is refused "
        . "because the server is already full. Also known as "
        . "RPL_SLINE (AustHex), and RPL_REDIR",
    "seealso"  => "010",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"        => "RPL_ISUPPORT",
    "numeric"     => "5",
    "information" => "http://www.irc.org/tech_docs/005.html",
    "comment"     => "Also known as RPL_PROTOCTL (Bahamut, Unreal, Ultimate)",
];

$irc2numerics[] = [
    "name"     => "RPL_MAP",
    "numeric"  => "6",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPEND",
    "numeric"  => "7",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_SNOMASK",
    "numeric" => "8",
    "origin"  => "ircu",
    "comment" => "Server notice mask (hex)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATMEMTOT",
    "numeric" => "9",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "RPL_BOUNCE",
    "numeric" => "10",
    "format"  => "<hostname> <port> :<info>",
    "comment" => "Sent to the client to redirect it to another server. Also "
        . "known as RPL_REDIR",
];

$irc2numerics[] = [
    "name"     => "RPL_STATMEM",
    "numeric"  => "10",
    "origin"   => "ircu",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_YOURCOOKIE",
    "numeric" => "14",
    "origin"  => "Hybrid?",
];

$irc2numerics[] = [
    "name"     => "RPL_MAP",
    "numeric"  => "15",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPMORE",
    "numeric"  => "16",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPEND",
    "numeric"  => "17",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_YOURID",
    "numeric" => "42",
    "origin"  => "IRCnet",
];

$irc2numerics[] = [
    "name"    => "RPL_SAVENICK",
    "numeric" => "43",
    "origin"  => "IRCnet",
    "format"  => ":<info>",
    "comment" => "Sent to the client when their nickname was forced to "
        . "change due to a collision",
];

$irc2numerics[] = [
    "name"    => "RPL_ATTEMPTINGJUNC",
    "numeric" => "50",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_ATTEMPTINGREROUTE",
    "numeric" => "51",
    "origin"  => "aircd",
];


$irc2numerics[] = "200-399, reply from server commands";

$irc2numerics[] = [
    "name"    => "RPL_TRACELINK",
    "numeric" => "200",
    "origin"  => "RFC1459",
    "format"  => "Link <version>[.<debug_level>] <destination> "
        . "<next_server> [V<protocol_version> "
        . "<link_uptime_in_seconds> <backstream_sendq> "
        . "<upstream_sendq>]",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACECONNECTING",
    "numeric" => "201",
    "origin"  => "RFC1459",
    "format"  => "Try. <class> <server>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEHANDSHAKE",
    "numeric" => "202",
    "origin"  => "RFC1459",
    "format"  => "H.S. <class> <server>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEUNKNOWN",
    "numeric" => "203",
    "origin"  => "RFC1459",
    "format"  => "???? <class> [<connection_address>]",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEOPERATOR",
    "numeric" => "204",
    "origin"  => "RFC1459",
    "format"  => "Oper <class> <nick>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEUSER",
    "numeric" => "205",
    "origin"  => "RFC1459",
    "format"  => "User <class> <nick>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACESERVER",
    "numeric" => "206",
    "origin"  => "RFC1459",
    "format"  => "Serv <class> <int>S <int>C <server> "
        . "<nick!user|*!*>@<host|server> [V<protocol_version>]",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACESERVICE",
    "numeric" => "207",
    "origin"  => "RFC2812",
    "format"  => "Service <class> <name> <type> <active_type>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACENEWTYPE",
    "numeric" => "208",
    "origin"  => "RFC1459",
    "format"  => "<newtype> 0 <client_name>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACECLASS",
    "numeric" => "209",
    "origin"  => "RFC2812",
    "format"  => "Class <class> <count>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"     => "RPL_TRACERECONNECT",
    "numeric"  => "210",
    "origin"   => "RFC2812",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATS",
    "numeric" => "210",
    "origin"  => "aircd",
    "comment" => "Used instead of having multiple stats numerics",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSLINKINFO",
    "numeric" => "211",
    "origin"  => "RFC1459",
    "format"  => "<linkname> <sendq> <sent_msgs> <sent_bytes> <recvd_msgs> "
        . "<rcvd_bytes> <time_open>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSCOMMANDS",
    "numeric" => "212",
    "origin"  => "RFC1459",
    "format"  => "<command> <count> [<byte_count> <remote_count>]",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSCLINE",
    "numeric" => "213",
    "origin"  => "RFC1459",
    "format"  => "C <host> * <name> <port> <class>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSNLINE",
    "numeric"  => "214",
    "origin"   => "RFC1459",
    "format"   => "N <host> * <name> <port> <class>",
    "comment"  => "Reply to STATS (See RFC), Also known as RPL_STATSOLDNLINE "
        . "(ircu, Unreal)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSILINE",
    "numeric" => "215",
    "origin"  => "RFC1459",
    "format"  => "I <host> * <host> <port> <class>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSKLINE",
    "numeric" => "216",
    "origin"  => "RFC1459",
    "format"  => "K <host> * <username> <port> <class>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSQLINE",
    "numeric"  => "217",
    "origin"   => "RFC1459",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSPLINE",
    "numeric"  => "217",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSYLINE",
    "numeric" => "218",
    "origin"  => "RFC1459",
    "format"  => "Y <class> <ping_freq> <connect_freq> <max_sendq>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFSTATS",
    "numeric" => "219",
    "origin"  => "RFC1459",
    "format"  => "<query> :<info>",
    "comment" => "End of RPL_STATS* list.",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSPLINE",
    "numeric"  => "220",
    "origin"   => "Hybrid",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSBLINE",
    "numeric"  => "220",
    "origin"   => "Bahamut, Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_UMODEIS",
    "numeric" => "221",
    "origin"  => "RFC1459",
    "format"  => "<user_modes> [<user_mode_params>]",
    "comment" => "Information about a user's own modes. Some daemons have "
        . "extended the mode command and certain modes take "
        . "parameters (like channel modes).",
];

$irc2numerics[] = [
    "name"     => "RPL_MODLIST",
    "numeric"  => "222",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_SQLINE_NICK",
    "numeric"  => "222",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSBLINE",
    "numeric"  => "222",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSELINE",
    "numeric"  => "223",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSGLINE",
    "numeric"  => "223",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSFLINE",
    "numeric"  => "224",
    "origin"   => "Hybrid, Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSTLINE",
    "numeric"  => "224",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDLINE",
    "numeric"  => "225",
    "origin"   => "Hybrid",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSZLINE",
    "numeric"  => "225",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSELINE",
    "numeric"  => "225",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSCOUNT",
    "numeric"  => "226",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSNLINE",
    "numeric"  => "226",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSGLINE",
    "numeric"  => "227",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSVLINE",
    "numeric"  => "227",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSQLINE",
    "numeric"  => "228",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_SERVICEINFO",
    "numeric"  => "231",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFSERVICES",
    "numeric"  => "232",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_RULES",
    "origin"   => "Unreal",
    "numeric"  => "232",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_SERVICE",
    "numeric"  => "233",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_SERVLIST",
    "numeric" => "234",
    "origin"  => "RFC2812",
    "format"  => "<name> <server> <mask> <type> <hopcount> <info>",
    "comment" => "A service entry in the service list",
];

$irc2numerics[] = [
    "name"    => "RPL_SERVLISTEND",
    "numeric" => "235",
    "origin"  => "RFC2812",
    "format"  => "<mask> <type> :<info>",
    "comment" => "Termination of an RPL_SERVLIST list",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSVERBOSE",
    "numeric" => "236",
    "origin"  => "ircu",
    "comment" => "Verbose server list?",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSENGINE",
    "numeric" => "237",
    "origin"  => "ircu",
    "comment" => "Engine name?",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSFLINE",
    "numeric"  => "238",
    "origin"   => "ircu",
    "comment"  => "Feature lines?",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSIAUTH",
    "numeric" => "239",
    "origin"  => "IRCnet",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSVLINE",
    "numeric"  => "240",
    "origin"   => "RFC2812",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSXLINE",
    "numeric"  => "240",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSLLINE",
    "numeric" => "241",
    "origin"  => "RFC1459",
    "format"  => "L <hostmask> * <servername> <maxdepth>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSUPTIME",
    "numeric" => "242",
    "origin"  => "RFC1459",
    "format"  => ":Server Up <days> days <hours>:<minutes>:<seconds>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSOLINE",
    "numeric" => "243",
    "origin"  => "RFC1459",
    "format"  => "O <hostmask> * <nick> [:<info>]",
    "comment" => "Reply to STATS (See RFC); The info field is an extension "
        . "found in some IRC daemons, which returns info such as an "
        . "e-mail address or the name/job of an operator",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSHLINE",
    "numeric" => "244",
    "origin"  => "RFC1459",
    "format"  => "H <hostmask> * <servername>",
    "comment" => "Reply to STATS (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSSLINE",
    "numeric" => "245",
    "origin"  => "Bahamut, IRCnet, Hybrid",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSPING",
    "numeric"  => "246",
    "origin"   => "RFC2812",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSTLINE",
    "numeric"  => "246",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSULINE",
    "numeric"  => "246",
    "origin"   => "Hybrid",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSBLINE",
    "numeric"  => "247",
    "origin"   => "RFC2812",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSXLINE",
    "numeric"  => "247",
    "origin"   => "Hybrid, PTlink, Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSGLINE",
    "numeric"  => "247",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSULINE",
    "numeric"  => "248",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDEFINE",
    "numeric"  => "248",
    "origin"   => "IRCnet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSULINE",
    "numeric"  => "249",
    "comment"  => "Extension to RFC1459?",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDEBUG",
    "numeric"  => "249",
    "origin"   => "Hybrid",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDLINE",
    "numeric"  => "250",
    "origin"   => "RFC2812",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_STATSCONN",
    "numeric" => "250",
    "origin"  => "ircu, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSERCLIENT",
    "numeric" => "251",
    "origin"  => "RFC1459",
    "format"  => ":There are <int> users and <int> invisible on <int> "
        . "servers",
    "comment" => "Reply to LUSERS command, other versions exist (eg. "
        . "RFC2812); Text may vary.",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSEROP",
    "numeric" => "252",
    "origin"  => "RFC1459",
    "format"  => "<int> :<info>",
    "comment" => "Reply to LUSERS command - Number of IRC operators online",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSERUNKNOWN",
    "numeric" => "253",
    "origin"  => "RFC1459",
    "format"  => "<int> :<info>",
    "comment" => "Reply to LUSERS command - Number of unknown/unregistered "
        . "connections",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSERCHANNELS",
    "numeric" => "254",
    "origin"  => "RFC1459",
    "format"  => "<int> :<info>",
    "comment" => "Reply to LUSERS command - Number of channels formed",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSERME",
    "numeric" => "255",
    "origin"  => "RFC1459",
    "format"  => ":I have <int> clients and <int> servers",
    "comment" => "Reply to LUSERS command - Information about local "
        . "connections; Text may vary.",
];

$irc2numerics[] = [
    "name"    => "RPL_ADMINME",
    "numeric" => "256",
    "origin"  => "RFC1459",
    "format"  => "<server> :<info>",
    "comment" => "Start of an RPL_ADMIN* reply. In practise, the server "
        . "parameter is often never given, and instead the info "
        . "field contains the text 'Administrative info about "
        . "<server>'. Newer daemons seem to follow the RFC and output "
        . "the server's hostname in the 'server' parameter, but also "
        . "output the server name in the text as per traditional "
        . "daemons.",
];

$irc2numerics[] = [
    "name"    => "RPL_ADMINLOC1",
    "numeric" => "257",
    "origin"  => "RFC1459",
    "format"  => ":<admin_location>",
    "comment" => "Reply to ADMIN command (Location, first line)",
];

$irc2numerics[] = [
    "name"    => "RPL_ADMINLOC2",
    "numeric" => "258",
    "origin"  => "RFC1459",
    "format"  => ":<admin_location>",
    "comment" => "Reply to ADMIN command (Location, second line)",
];

$irc2numerics[] = [
    "name"    => "RPL_ADMINEMAIL",
    "numeric" => "259",
    "origin"  => "RFC1459",
    "format"  => ":<email_address>",
    "comment" => "Reply to ADMIN command (E-mail address of administrator)",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACELOG",
    "numeric" => "261",
    "origin"  => "RFC1459",
    "format"  => "File <logfile> <debug_level>",
    "comment" => "See RFC",
];

$irc2numerics[] = [
    "name"     => "RPL_TRACEPING",
    "numeric"  => "262",
    "comment"  => "Extension to RFC1459?",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_TRACEEND",
    "numeric"  => "262",
    "origin"   => "RFC2812",
    "format"   => "<server_name> <version>[.<debug_level>] :<info>",
    "comment"  => "Used to terminate a list of RPL_TRACE* replies",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_TRYAGAIN",
    "numeric" => "263",
    "origin"  => "RFC2812",
    "format"  => "<command> :<info>",
    "comment" => "When a server drops a command without processing it, it "
        . "MUST use this reply. Also known as RPL_LOAD_THROTTLED and "
        . "RPL_LOAD2HI, I'm presuming they do the same thing.",
];

$irc2numerics[] = [
    "name"    => "RPL_LOCALUSERS",
    "numeric" => "265",
    "origin"  => "aircd, Hybrid, Hybrid, Bahamut",
    "comment" => "Also known as RPL_CURRENT_LOCAL",
];

$irc2numerics[] = [
    "name"    => "RPL_GLOBALUSERS",
    "numeric" => "266",
    "origin"  => "aircd, Hybrid, Hybrid, Bahamut",
    "comment" => "Also known as RPL_CURRENT_GLOBAL",
];

$irc2numerics[] = [
    "name"    => "RPL_START_NETSTAT",
    "numeric" => "267",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_NETSTAT",
    "numeric" => "268",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_END_NETSTAT",
    "numeric" => "269",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_PRIVS",
    "numeric" => "270",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "RPL_SILELIST",
    "numeric" => "271",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFSILELIST",
    "numeric" => "272",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "RPL_NOTIFY",
    "numeric" => "273",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDNOTIFY",
    "numeric"  => "274",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDELTA",
    "numeric"  => "274",
    "origin"   => "IRCnet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_STATSDLINE",
    "numeric"  => "275",
    "origin"   => "ircu, Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_VCHANEXIST",
    "numeric" => "276",
];

$irc2numerics[] = [
    "name"    => "RPL_VCHANLIST",
    "numeric" => "277",
];

$irc2numerics[] = [
    "name"    => "RPL_VCHANHELP",
    "numeric" => "278",
];

$irc2numerics[] = [
    "name"    => "RPL_GLIST",
    "numeric" => "280",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFGLIST",
    "numeric"  => "281",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ACCEPTLIST",
    "numeric"  => "281",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFACCEPT",
    "numeric"  => "282",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_JUPELIST",
    "numeric"  => "282",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ALIST",
    "numeric"  => "283",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFJUPELIST",
    "numeric"  => "283",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFALIST",
    "numeric"  => "284",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_FEATURE",
    "numeric"  => "284",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_GLIST_HASH",
    "numeric"  => "285",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_HANDLE",
    "numeric"  => "285",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_NEWHOSTIS",
    "numeric"  => "285",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_USERS",
    "numeric"  => "286",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHKHEAD",
    "numeric"  => "286",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_CHOPS",
    "numeric"  => "287",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANUSER",
    "numeric"  => "287",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_VOICES",
    "numeric"  => "288",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_PATCHHEAD",
    "numeric"  => "288",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_AWAY",
    "numeric"  => "289",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_PATCHCON",
    "numeric"  => "289",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_OPERS",
    "numeric"  => "290",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPHDR",
    "numeric"  => "290",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_DATASTR",
    "numeric"  => "290",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_BANNED",
    "numeric"  => "291",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPOP",
    "numeric"  => "291",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFCHECK",
    "numeric"  => "291",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_BANS",
    "numeric"  => "292",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPTLR",
    "numeric"  => "292",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_INVITE",
    "numeric"  => "293",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPHLP",
    "numeric"  => "293",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_INVITES",
    "numeric"  => "294",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPFWD",
    "numeric"  => "294",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANINFO_KICK",
    "numeric"  => "295",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_HELPIGN",
    "numeric"  => "295",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_CHANINFO_KICKS",
    "numeric" => "296",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_END_CHANINFO",
    "numeric" => "299",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "RPL_NONE",
    "numeric" => "300",
    "origin"  => "RFC1459",
    "comment" => "Dummy reply, supposedly only used for debugging/testing "
        . "new features, however has appeared in production daemons.",
];

$irc2numerics[] = [
    "name"    => "RPL_AWAY",
    "numeric" => "301",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<message>",
    "comment" => "Used in reply to a command directed at a user who is "
        . "marked as away",
];

$irc2numerics[] = [
    "name"     => "RPL_AWAY",
    "numeric"  => "301",
    "origin"   => "KineIRCd",
    "format"   => "<nick> <seconds away> :<message>",
    "comment"  => "Identical to RPL_AWAY, however this includes the number of "
        . "seconds the user has been away for. This is designed to "
        . "discourage the need for people to use those horrible "
        . "scripts which set the AWAY message every 30 seconds in "
        . "order to include an 'away since' timer.",
    "repeated" => true,
];

$irc2numerics[] = [
    "name"    => "RPL_USERHOST",
    "numeric" => "302",
    "origin"  => "RFC1459",
    "format"  => ":*1<reply> *( ' ' <reply> )",
    "comment" => "Reply used by USERHOST (see RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_ISON",
    "numeric" => "303",
    "origin"  => "RFC1459",
    "format"  => ":*1<nick> *( ' ' <nick> )",
    "comment" => "Reply to the ISON command (see RFC)",
];

$irc2numerics[] = [
    "name"     => "RPL_TEXT",
    "numeric"  => "304",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_UNAWAY",
    "numeric" => "305",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Reply from AWAY when no longer marked as away",
];

$irc2numerics[] = [
    "name"    => "RPL_NOWAWAY",
    "numeric" => "306",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Reply from AWAY when marked away",
];

$irc2numerics[] = [
    "name"     => "RPL_USERIP",
    "numeric"  => "307",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISREGNICK",
    "numeric"  => "307",
    "origin"   => "Bahamut, Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_SUSERHOST",
    "numeric"  => "307",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_NOTIFYACTION",
    "numeric"  => "308",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISADMIN",
    "numeric"  => "308",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_RULESSTART",
    "numeric"  => "308",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_NICKTRACE",
    "numeric"  => "309",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISSADMIN",
    "numeric"  => "309",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFRULES",
    "numeric"  => "309",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISHELPER",
    "numeric"  => "309",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISSVCMSG",
    "numeric"  => "310",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISHELPOP",
    "numeric"  => "310",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISSERVICE",
    "numeric"  => "310",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISUSER",
    "numeric" => "311",
    "origin"  => "RFC1459",
    "format"  => "<nick> <user> <host> * :<real_name>",
    "comment" => "Reply to WHOIS - Information about the user",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISSERVER",
    "numeric" => "312",
    "origin"  => "RFC1459",
    "format"  => "<nick> <server> :<server_info>",
    "comment" => "Reply to WHOIS - What server they're on",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISOPERATOR",
    "numeric" => "313",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<privileges>",
    "comment" => "Reply to WHOIS - User has IRC Operator privileges",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOWASUSER",
    "numeric" => "314",
    "origin"  => "RFC1459",
    "format"  => "<nick> <user> <host> * :<real_name>",
    "comment" => "Reply to WHOWAS - Information about the user",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFWHO",
    "numeric" => "315",
    "origin"  => "RFC1459",
    "format"  => "<name> :<info>",
    "comment" => "Used to terminate a list of RPL_WHOREPLY replies",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISCHANOP",
    "numeric"  => "316",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISIDLE",
    "numeric" => "317",
    "origin"  => "RFC1459",
    "format"  => "<nick> <seconds> :seconds idle",
    "comment" => "Reply to WHOIS - Idle information",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFWHOIS",
    "numeric" => "318",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<info>",
    "comment" => "Reply to WHOIS - End of list",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISCHANNELS",
    "numeric" => "319",
    "origin"  => "RFC1459",
    "format"  => "<nick> :*( ( '@' / '+' ) <channel> ' ' )",
    "comment" => "Reply to WHOIS - Channel list for user (See RFC)",
];

$irc2numerics[] = [
    "name"      => "RPL_WHOISVIRT",
    "numeric"   => "320",
    "origin"    => "AustHex",
    "contact"   => "dev@austnet.org",
    "conflicts" => "yes",
];

$irc2numerics[] = [
    "name"      => "RPL_WHOIS_HIDDEN",
    "numeric"   => "320",
    "origin"    => "Anothernet",
    "conflicts" => "yes",
];

$irc2numerics[] = [
    "name"      => "RPL_WHOISSPECIAL",
    "numeric"   => "320",
    "origin"    => "Unreal",
    "conflicts" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_LISTSTART",
    "numeric"  => "321",
    "origin"   => "RFC1459",
    "format"   => "Channels :Users  Name",
    "comment"  => "Channel list - Header",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_LIST",
    "numeric" => "322",
    "origin"  => "RFC1459",
    "format"  => "<channel> <#_visible> :<topic>",
    "comment" => "Channel list - A channel",
];

$irc2numerics[] = [
    "name"    => "RPL_LISTEND",
    "numeric" => "323",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Channel list - End of list",
];

$irc2numerics[] = [
    "name"    => "RPL_CHANNELMODEIS",
    "numeric" => "324",
    "origin"  => "RFC1459",
    "format"  => "<channel> <mode> <mode_params>",
];

$irc2numerics[] = [
    "name"     => "RPL_UNIQOPIS",
    "numeric"  => "325",
    "origin"   => "RFC2812",
    "format"   => "<channel> <nickname>",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANNELPASSIS",
    "numeric"  => "325",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_NOCHANPASS",
    "numeric" => "326",
];

$irc2numerics[] = [
    "name"    => "RPL_CHPASSUNKNOWN",
    "numeric" => "327",
];

$irc2numerics[] = [
    "name"    => "RPL_CHANNEL_URL",
    "numeric" => "328",
    "origin"  => "Bahamut, AustHex",
];

$irc2numerics[] = [
    "name"    => "RPL_CREATIONTIME",
    "numeric" => "329",
    "origin"  => "Bahamut",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOWAS_TIME",
    "numeric"  => "330",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISACCOUNT",
    "numeric"  => "330",
    "origin"   => "ircu",
    "format"   => "<nick> <authname> :<info>",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_NOTOPIC",
    "numeric" => "331",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<info>",
    "comment" => "Response to TOPIC when no topic is set",
];

$irc2numerics[] = [
    "name"    => "RPL_TOPIC",
    "numeric" => "332",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<topic>",
    "comment" => "Response to TOPIC with the set topic",
];

$irc2numerics[] = [
    "name"    => "RPL_TOPICWHOTIME",
    "numeric" => "333",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"     => "RPL_LISTUSAGE",
    "numeric"  => "334",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_COMMANDSYNTAX",
    "numeric"  => "334",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_LISTSYNTAX",
    "numeric"  => "334",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISBOT",
    "numeric"  => "335",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CHANPASSOK",
    "numeric"  => "338",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISACTUALLY",
    "numeric"  => "338",
    "origin"   => "ircu, Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_BADCHANPASS",
    "numeric" => "339",
];

$irc2numerics[] = [
    "name"    => "RPL_USERIP",
    "numeric" => "340",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "RPL_INVITING",
    "numeric" => "341",
    "origin"  => "RFC1459",
    "format"  => "<nick> <channel>",
    "comment" => "Returned by the server to indicate that the attempted "
        . "INVITE message was successful and is being passed onto "
        . "the end client. Note that RFC1459 documents the parameters "
        . "in the reverse order. The format given here is the format "
        . "used on production servers, and should be considered the "
        . "standard reply above that given by RFC1459.",
];

$irc2numerics[] = [
    "name"     => "RPL_SUMMONING",
    "numeric"  => "342",
    "origin"   => "RFC1459",
    "format"   => "<user> :<info>",
    "comment"  => "Returned by a server answering a SUMMON message to indicate that it is summoning that user",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_INVITED",
    "numeric" => "345",
    "origin"  => "GameSurge",
    "contact" => "gsdev@gamesurge.net",
    "format"  => "<channel> <user being invited> <user issuing invite> "
        . ":<user being invited> has been invited by "
        . "<user issuing invite>",
    "comment" => "Sent to users on a channel when an INVITE command has been "
        . "issued",
];

$irc2numerics[] = [
    "name"    => "RPL_INVITELIST",
    "numeric" => "346",
    "origin"  => "RFC2812",
    "format"  => "<channel> <invitemask>",
    "comment" => "An invite mask for the invite mask list",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFINVITELIST",
    "numeric" => "347",
    "origin"  => "RFC2812",
    "format"  => "<channel> :<info>",
    "comment" => "Termination of an RPL_INVITELIST list",
];

$irc2numerics[] = [
    "name"    => "RPL_EXCEPTLIST",
    "numeric" => "348",
    "origin"  => "RFC2812",
    "format"  => "<channel> <exceptionmask>",
    "comment" => "An exception mask for the exception mask list. Also known "
        . "as RPL_EXLIST (Unreal, Ultimate)",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFEXCEPTLIST",
    "numeric" => "349",
    "origin"  => "RFC2812",
    "format"  => "<channel> :<info>",
    "comment" => "Termination of an RPL_EXCEPTLIST list. Also known as "
        . "RPL_ENDOFEXLIST (Unreal, Ultimate)",
];

$irc2numerics[] = [
    "name"    => "RPL_VERSION",
    "numeric" => "351",
    "origin"  => "RFC1459",
    "format"  => "<version>[.<debuglevel>] <server> :<comments>",
    "comment" => "Reply by the server showing its version details, however "
        . "this format is not often adhered to",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOREPLY",
    "numeric" => "352",
    "origin"  => "RFC1459",
    "format"  => "<channel> <user> <host> <server> <nick> <H|G>[*][@|+] "
        . ":<hopcount> <real_name>",
    "comment" => "Reply to vanilla WHO (See RFC). This format can be "
        . "very different if the 'WHOX' version of the command is "
        . "used (see ircu).",
];

$irc2numerics[] = [
    "name"    => "RPL_NAMREPLY",
    "numeric" => "353",
    "origin"  => "RFC1459",
    "format"  => "( '=' / '*' / '@' ) <channel> ' ' : [ '@' / '+' ] <nick> "
        . "*( ' ' [ '@' / '+' ] <nick> )",
    "comment" => "Reply to NAMES (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOSPCRPL",
    "numeric" => "354",
    "origin"  => "ircu",
    "comment" => "Reply to WHO, however it is a 'special' reply because it "
        . "is returned using a non-standard (non-RFC1459) format. The "
        . "format is dictated by the command given by the user, and "
        . "can vary widely. When this is used, the WHO command was "
        . "invoked in its 'extended' form, as announced by the 'WHOX' "
        . "ISUPPORT tag.",
];

$irc2numerics[] = [
    "name"    => "RPL_NAMREPLY_",
    "numeric" => "355",
    "origin"  => "QuakeNet",
    "format"  => "( '=' / '*' / '@' ) <channel> ' ' : [ '@' / '+' ] <nick> "
        . "*( ' ' [ '@' / '+' ] <nick> )",
    "comment" => "Reply to the \"NAMES -d\" command - used to show invisible "
        . "users (when the channel is set +D, QuakeNet relative). The "
        . "proper define name for this numeric is unknown at this "
        . "time",
    "seealso" => "353",
];

$irc2numerics[] = [
    "name"     => "RPL_MAP",
    "numeric"  => "357",
    "origin"   => "AustHex",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPMORE",
    "numeric"  => "358",
    "origin"   => "AustHex",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPEND",
    "numeric"  => "359",
    "origin"   => "AustHex",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_KILLDONE",
    "numeric"  => "361",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CLOSING",
    "numeric"  => "362",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_CLOSEEND",
    "numeric"  => "363",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_LINKS",
    "numeric" => "364",
    "origin"  => "RFC1459",
    "format"  => "<mask> <server> :<hopcount> <server_info>",
    "comment" => "Reply to the LINKS command",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFLINKS",
    "numeric" => "365",
    "origin"  => "RFC1459",
    "format"  => "<mask> :<info>",
    "comment" => "Termination of an RPL_LINKS list",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFNAMES",
    "numeric" => "366",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<info>",
    "comment" => "Termination of an RPL_NAMREPLY list",
];

$irc2numerics[] = [
    "name"    => "RPL_BANLIST",
    "numeric" => "367",
    "origin"  => "RFC1459",
    "format"  => "<channel> <banid> [<time_left> :<reason>]",
    "comment" => "A ban-list item (See RFC); <time left> and <reason> are "
        . "additions used by KineIRCd",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFBANLIST",
    "numeric" => "368",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<info>",
    "comment" => "Termination of an RPL_BANLIST list",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFWHOWAS",
    "numeric" => "369",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<info>",
    "comment" => "Reply to WHOWAS - End of list",
];

$irc2numerics[] = [
    "name"    => "RPL_INFO",
    "numeric" => "371",
    "origin"  => "RFC1459",
    "format"  => ":<string>",
    "comment" => "Reply to INFO",
];

$irc2numerics[] = [
    "name"    => "RPL_MOTD",
    "numeric" => "372",
    "origin"  => "RFC1459",
    "format"  => ":- <string>",
    "comment" => "Reply to MOTD",
];

$irc2numerics[] = [
    "name"     => "RPL_INFOSTART",
    "numeric"  => "373",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFINFO",
    "numeric" => "374",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Termination of an RPL_INFO list",
];

$irc2numerics[] = [
    "name"    => "RPL_MOTDSTART",
    "numeric" => "375",
    "origin"  => "RFC1459",
    "format"  => ":- <server> Message of the day -",
    "comment" => "Start of an RPL_MOTD list",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFMOTD",
    "numeric" => "376",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Termination of an RPL_MOTD list",
];

$irc2numerics[] = [
    "name"     => "RPL_KICKEXPIRED",
    "numeric"  => "377",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_SPAM",
    "numeric"  => "377",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "format"   => ":<text>",
    "comment"  => "Used during the connection (after MOTD) to announce the "
        . "network policy on spam and privacy. Supposedly now "
        . "obsoleted in favour of using NOTICE.",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_BANEXPIRED",
    "numeric"  => "378",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISHOST",
    "numeric"  => "378",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MOTD",
    "numeric"  => "378",
    "origin"   => "AustHex",
    "comment"  => "Used by AustHex to 'force' the display of the MOTD, "
        . "however is considered obsolete due to client/script "
        . "awareness & ability to ",
    "seealso"  => "372",
    "obsolete" => "yes",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_KICKLINKED",
    "numeric"  => "379",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISMODES",
    "numeric"  => "379",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_BANLINKED",
    "numeric"  => "380",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_YOURHELPER",
    "numeric"  => "380",
    "origin"   => "AustHex",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_YOUREOPER",
    "numeric" => "381",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Successful reply from OPER",
];

$irc2numerics[] = [
    "name"    => "RPL_REHASHING",
    "numeric" => "382",
    "origin"  => "RFC1459",
    "format"  => "<config_file> :<info>",
    "comment" => "Successful reply from REHASH",
];

$irc2numerics[] = [
    "name"    => "RPL_YOURESERVICE",
    "numeric" => "383",
    "origin"  => "RFC2812",
    "format"  => ":You are service <service_name>",
    "comment" => "Sent upon successful registration of a service",
];

$irc2numerics[] = [
    "name"     => "RPL_MYPORTIS",
    "numeric"  => "384",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_NOTOPERANYMORE",
    "numeric" => "385",
    "origin"  => "AustHex, Hybrid, Unreal",
];

$irc2numerics[] = [
    "name"     => "RPL_QLIST",
    "numeric"  => "386",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_IRCOPS",
    "numeric"  => "386",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFQLIST",
    "numeric"  => "387",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFIRCOPS",
    "numeric"  => "387",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_ALIST",
    "numeric" => "388",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFALIST",
    "numeric" => "389",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_TIME",
    "numeric" => "391",
    "origin"  => "RFC1459",
    "format"  => "<server> :<time string>",
    "comment" => "Response to the TIME command. The string format may vary "
        . "greatly.",
    "seealso" => "679",
];

$irc2numerics[] = [
    "name"     => "RPL_TIME",
    "numeric"  => "391",
    "origin"   => "ircu",
    "format"   => "<server> <timestamp> <offset> :<time string>",
    "comment"  => "This extention adds the timestamp and timestamp-offet "
        . "information for clients.",
    "conflict" => "yes",
    "repeated" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_TIME",
    "numeric"  => "391",
    "origin"   => "bdq-ircd",
    "format"   => "<server> <timezone name> <microseconds> :<time string>",
    "comment"  => "Timezone name is acronym style (eg. 'EST', 'PST' etc). The "
        . "microseconds field is the number of microseconds since "
        . "the UNIX epoch, however it is relative to the local "
        . "timezone of the server. The timezone field is ambiguous, "
        . "since it only appears to include American zones.",
    "conflict" => "yes",
    "repeated" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_TIME",
    "numeric"  => "391",
    "format"   => "<server> <year> <month> <day> <hour> <minute> <second>",
    "comment"  => "Yet another variation, including the time broken down "
        . "into its components. Time is supposedly relative to UTC.",
    "conflict" => "yes",
    "repeated" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_USERSSTART",
    "numeric" => "392",
    "origin"  => "RFC1459",
    "format"  => ":UserID   Terminal  Host",
    "comment" => "Start of an RPL_USERS list",
];

$irc2numerics[] = [
    "name"    => "RPL_USERS",
    "numeric" => "393",
    "origin"  => "RFC1459",
    "format"  => ":<username> <ttyline> <hostname>",
    "comment" => "Response to the USERS command (See RFC)",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFUSERS",
    "numeric" => "394",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Termination of an RPL_USERS list",
];

$irc2numerics[] = [
    "name"    => "RPL_NOUSERS",
    "numeric" => "395",
    "origin"  => "RFC1459",
    "format"  => ":<info>",
    "comment" => "Reply to USERS when nobody is logged in",
];

$irc2numerics[] = [
    "name"    => "RPL_HOSTHIDDEN",
    "numeric" => "396",
    "origin"  => "Undernet",
    "comment" => "Reply to a user when user mode +x (host masking) was set "
        . "successfully",
];


$irc2numerics[] = "400-599, errors";

$irc2numerics[] = [
    "name"    => "ERR_UNKNOWNERROR",
    "numeric" => "400",
    "format"  => "<command> [<?>] :<info>",
    "comment" => "Sent when an error occured executing a command, but it is "
        . "not specifically known why the command could not be "
        . "executed.",
];

$irc2numerics[] = [
    "name"    => "ERR_NOSUCHNICK",
    "numeric" => "401",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<reason>",
    "comment" => "Used to indicate the nickname parameter supplied to a "
        . "command is currently unused",
];

$irc2numerics[] = [
    "name"    => "ERR_NOSUCHSERVER",
    "numeric" => "402",
    "origin"  => "RFC1459",
    "format"  => "<server> :<reason>",
    "comment" => "Used to indicate the server name given currently doesn't "
        . "exist",
];

$irc2numerics[] = [
    "name"    => "ERR_NOSUCHCHANNEL",
    "numeric" => "403",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Used to indicate the given channel name is invalid, or "
        . "does not exist",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTSENDTOCHAN",
    "numeric" => "404",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Sent to a user who does not have the rights to send a "
        . "message to a channel",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYCHANNELS",
    "numeric" => "405",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Sent to a user when they have joined the maximum number "
        . "of allowed channels and they tried to join another channel",
];

$irc2numerics[] = [
    "name"    => "ERR_WASNOSUCHNICK",
    "numeric" => "406",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<reason>",
    "comment" => "Returned by WHOWAS to indicate there was no history "
        . "information for a given nickname",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYTARGETS",
    "numeric" => "407",
    "origin"  => "RFC1459",
    "format"  => "<target> :<reason>",
    "comment" => "The given target(s) for a command are ambiguous in that "
        . "they relate to too many targets",
];

$irc2numerics[] = [
    "name"    => "ERR_NOSUCHSERVICE",
    "numeric" => "408",
    "origin"  => "RFC2812",
    "format"  => "<service_name> :<reason>",
    "comment" => "Returned to a client which is attempting to send an "
        . "SQUERY (or other message) to a service which does not "
        . "exist",
];

$irc2numerics[] = [
    "name"     => "ERR_NOCOLORSONCHAN",
    "numeric"  => "408",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NOORIGIN",
    "numeric" => "409",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "PING or PONG message missing the originator parameter "
        . "which is required since these commands must work without "
        . "valid prefixes",
];

$irc2numerics[] = [
    "name"    => "ERR_NORECIPIENT",
    "numeric" => "411",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned when no recipient is given with a command",
];

$irc2numerics[] = [
    "name"    => "ERR_NOTEXTTOSEND",
    "numeric" => "412",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned when NOTICE/PRIVMSG is used with no message given",
];

$irc2numerics[] = [
    "name"    => "ERR_NOTOPLEVEL",
    "numeric" => "413",
    "origin"  => "RFC1459",
    "format"  => "<mask> :<reason>",
    "comment" => "Used when a message is being sent to a mask without being "
        . "limited to a top-level domain (i.e. * instead of *.au)",
];

$irc2numerics[] = [
    "name"    => "ERR_WILDTOPLEVEL",
    "numeric" => "414",
    "origin"  => "RFC1459",
    "format"  => "<mask> :<reason>",
    "comment" => "Used when a message is being sent to a mask with a "
        . "wild-card for a top level domain (i.e. *.*)",
];

$irc2numerics[] = [
    "name"    => "ERR_BADMASK",
    "numeric" => "415",
    "origin"  => "RFC2812",
    "format"  => "<mask> :<reason>",
    "comment" => "Used when a message is being sent to a mask with an "
        . "invalid syntax",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYMATCHES",
    "numeric" => "416",
    "origin"  => "IRCnet",
    "format"  => "<command> [<mask>] :<info>",
    "comment" => "Returned when too many matches have been found for a "
        . "command and the output has been truncated. An example "
        . "would be the WHO command, where by the mask '*' would "
        . "match everyone on the network! Ouch!",
];

$irc2numerics[] = [
    "name"     => "ERR_QUERYTOOLONG",
    "numeric"  => "416",
    "origin"   => "ircu",
    "comment"  => "Same as ERR_TOOMANYMATCHES",
    "repeated" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_LENGTHTRUNCATED",
    "numeric" => "419",
    "origin"  => "aircd",
];

$irc2numerics[] = [
    "name"    => "ERR_UNKNOWNCOMMAND",
    "numeric" => "421",
    "origin"  => "RFC1459",
    "format"  => "<command> :<reason>",
    "comment" => "Returned when the given command is unknown to the server "
        . "(or hidden because of lack of access rights)",
];

$irc2numerics[] = [
    "name"    => "ERR_NOMOTD",
    "numeric" => "422",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Sent when there is no MOTD to send the client",
];

$irc2numerics[] = [
    "name"    => "ERR_NOADMININFO",
    "numeric" => "423",
    "origin"  => "RFC1459",
    "format"  => "<server> :<reason>",
    "comment" => "Returned by a server in response to an ADMIN request when "
        . "no information is available. RFC1459 mentions this in the "
        . "list of numerics. While it's not listed as a valid reply "
        . "in section 4.3.7 ('Admin command'), it's confirmed to "
        . "exist in the real world.",
];

$irc2numerics[] = [
    "name"    => "ERR_FILEERROR",
    "numeric" => "424",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Generic error message used to report a failed file "
        . "operation during the processing of a command",
];

$irc2numerics[] = [
    "name"    => "ERR_NOOPERMOTD",
    "numeric" => "425",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYAWAY",
    "numeric" => "429",
    "origin"  => "Bahamut",
];

$irc2numerics[] = [
    "name"    => "ERR_EVENTNICKCHANGE",
    "numeric" => "430",
    "origin"  => "AustHex",
    "contact" => "dev@austnet.org",
    "comment" => "Returned by NICK when the user is not allowed to change "
        . "their nickname due to a channel event (channel mode +E)",
];

$irc2numerics[] = [
    "name"         => "ERR_NONICKNAMEGIVEN",
    "numeric"      => "431",
    "origin"       => "RFC1459",
    "format"       => ":<reason>",
    "comment"      => "Returned when a nickname parameter expected for a command "
        . "isn't found",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"         => "ERR_ERRONEUSNICKNAME",
    "numeric"      => "432",
    "origin"       => "RFC1459",
    "format"       => "<nick> :<reason>",
    "comment"      => "Returned after receiving a NICK message which contains a "
        . "nickname which is considered invalid, such as it's "
        . "reserved ('anonymous') or contains characters considered "
        . "invalid for nicknames. This numeric is misspelt, but "
        . "remains with this name for historical reasons :)",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"         => "ERR_NICKNAMEINUSE",
    "numeric"      => "433",
    "origin"       => "RFC1459",
    "format"       => "<nick> :<reason>",
    "comment"      => "Returned by the NICK command when the given nickname is "
        . "already in use",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_SERVICENAMEINUSE",
    "numeric"  => "434",
    "origin"   => "AustHex?",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_NORULES",
    "numeric"  => "434",
    "origin"   => "Unreal, Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_SERVICECONFUSED",
    "numeric"  => "435",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_BANONCHAN",
    "numeric"  => "435",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NICKCOLLISION",
    "numeric" => "436",
    "origin"  => "RFC1459",
    "format"  => "<nick> :<reason>",
    "comment" => "Returned by a server to a client when it detects a "
        . "nickname collision",
];

$irc2numerics[] = [
    "name"     => "ERR_UNAVAILRESOURCE",
    "numeric"  => "437",
    "origin"   => "RFC2812",
    "format"   => "<nick/channel/service> :<reason>",
    "comment"  => "Return when the target is unable to be reached "
        . "temporarily, eg. a delay mechanism in play, or a service "
        . "being offline",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_BANNICKCHANGE",
    "numeric"  => "437",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_NICKTOOFAST",
    "numeric"  => "438",
    "origin"   => "ircu",
    "comment"  => "Also known as ERR_NCHANGETOOFAST (Unreal, Ultimate)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_DEAD",
    "numeric"  => "438",
    "origin"   => "IRCnet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_TARGETTOOFAST",
    "numeric" => "439",
    "origin"  => "ircu",
    "comment" => "Also known as many other things, RPL_INVTOOFAST, "
        . "RPL_MSGTOOFAST etc",
];

$irc2numerics[] = [
    "name"    => "ERR_SERVICESDOWN",
    "numeric" => "440",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "ERR_USERNOTINCHANNEL",
    "numeric" => "441",
    "origin"  => "RFC1459",
    "format"  => "<nick> <channel> :<reason>",
    "comment" => "Returned by the server to indicate that the target user "
        . "of the command is not on the given channel",
];

$irc2numerics[] = [
    "name"    => "ERR_NOTONCHANNEL",
    "numeric" => "442",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned by the server whenever a client tries to "
        . "perform a channel effecting command for which the client "
        . "is not a member",
];

$irc2numerics[] = [
    "name"    => "ERR_USERONCHANNEL",
    "numeric" => "443",
    "origin"  => "RFC1459",
    "format"  => "<nick> <channel> [:<reason>]",
    "comment" => "Returned when a client tries to invite a user to a "
        . "channel they're already on",
];

$irc2numerics[] = [
    "name"    => "ERR_NOLOGIN",
    "numeric" => "444",
    "origin"  => "RFC1459",
    "format"  => "<user> :<reason>",
    "comment" => "Returned by the SUMMON command if a given user was not "
        . "logged in and could not be summoned",
];

$irc2numerics[] = [
    "name"    => "ERR_SUMMONDISABLED",
    "numeric" => "445",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by SUMMON when it has been disabled or not "
        . "implemented",
];

$irc2numerics[] = [
    "name"    => "ERR_USERSDISABLED",
    "numeric" => "446",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by USERS when it has been disabled or not "
        . "implemented",
];

$irc2numerics[] = [
    "name"    => "ERR_NONICKCHANGE",
    "numeric" => "447",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"         => "ERR_NOTIMPLEMENTED",
    "numeric"      => "449",
    "origin"       => "Undernet",
    "format"       => "Unspecified",
    "comment"      => "Returned when a requested feature is not implemented "
        . "(and cannot be completed)",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"         => "ERR_NOTREGISTERED",
    "numeric"      => "451",
    "origin"       => "RFC1459",
    "format"       => ":<reason>",
    "comment"      => "Returned by the server to indicate that the client must "
        . "be registered before the server will allow it to be "
        . "parsed in detail",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_IDCOLLISION",
    "numeric" => "452",
];

$irc2numerics[] = [
    "name"    => "ERR_NICKLOST",
    "numeric" => "453",
];

$irc2numerics[] = [
    "name"    => "ERR_HOSTILENAME",
    "numeric" => "455",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"    => "ERR_ACCEPTFULL",
    "numeric" => "456",
];

$irc2numerics[] = [
    "name"    => "ERR_ACCEPTEXIST",
    "numeric" => "457",
];

$irc2numerics[] = [
    "name"    => "ERR_ACCEPTNOT",
    "numeric" => "458",
];

$irc2numerics[] = [
    "name"    => "ERR_NOHIDING",
    "numeric" => "459",
    "origin"  => "Unreal",
    "comment" => "Not allowed to become an invisible operator?",
];

$irc2numerics[] = [
    "name"    => "ERR_NOTFORHALFOPS",
    "numeric" => "460",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"         => "ERR_NEEDMOREPARAMS",
    "numeric"      => "461",
    "origin"       => "RFC1459",
    "format"       => "<command> :<reason>",
    "comment"      => "Returned by the server by any command which requires "
        . "more parameters than the number of parameters given",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"         => "ERR_ALREADYREGISTERED",
    "numeric"      => "462",
    "origin"       => "RFC1459",
    "format"       => ":<reason>",
    "comment"      => "Returned by the server to any link which attempts to "
        . "register again",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NOPERMFORHOST",
    "numeric" => "463",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned to a client which attempts to register with a "
        . "server which has been configured to refuse connections "
        . "from the client's host",
];

$irc2numerics[] = [
    "name"         => "ERR_PASSWDMISMATCH",
    "numeric"      => "464",
    "origin"       => "RFC1459",
    "format"       => ":<reason>",
    "comment"      => "Returned by the PASS command to indicate the given "
        . "password was required and was either not given or was "
        . "incorrect",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_YOUREBANNEDCREEP",
    "numeric" => "465",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned to a client after an attempt to register on a "
        . "server configured to ban connections from that client",
];

$irc2numerics[] = [
    "name"     => "ERR_YOUWILLBEBANNED",
    "numeric"  => "466",
    "origin"   => "RFC1459",
    "comment"  => "Sent by a server to a user to inform that access to the "
        . "server will soon be denied",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_KEYSET",
    "numeric" => "467",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned when the channel key for a channel has already "
        . "been set",
];

$irc2numerics[] = [
    "name"     => "ERR_INVALIDUSERNAME",
    "numeric"  => "468",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ONLYSERVERSCANCHANGE",
    "numeric"  => "468",
    "origin"   => "Bahamut, Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_LINKSET",
    "numeric" => "469",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"     => "ERR_LINKCHANNEL",
    "numeric"  => "470",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_KICKEDFROMCHAN",
    "numeric"  => "470",
    "origin"   => "aircd",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_CHANNELISFULL",
    "numeric" => "471",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned when attempting to join a channel which is set "
        . "+l and is already full",
];

$irc2numerics[] = [
    "name"    => "ERR_UNKNOWNMODE",
    "numeric" => "472",
    "origin"  => "RFC1459",
    "format"  => "<char> :<reason>",
    "comment" => "Returned when a given mode is unknown",
];

$irc2numerics[] = [
    "name"    => "ERR_INVITEONLYCHAN",
    "numeric" => "473",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned when attempting to join a channel which is "
        . "invite only without an invitation",
];

$irc2numerics[] = [
    "name"    => "ERR_BANNEDFROMCHAN",
    "numeric" => "474",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned when attempting to join a channel a user is "
        . "banned from",
];

$irc2numerics[] = [
    "name"    => "ERR_BADCHANNELKEY",
    "numeric" => "475",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned when attempting to join a key-locked channel "
        . "either without a key or with the wrong key",
];

$irc2numerics[] = [
    "name"    => "ERR_BADCHANMASK",
    "numeric" => "476",
    "origin"  => "RFC2812",
    "format"  => "<channel> :<reason>",
    "comment" => "The given channel mask was invalid",
];

$irc2numerics[] = [
    "name"     => "ERR_NOCHANMODES",
    "numeric"  => "477",
    "origin"   => "RFC2812",
    "format"   => "<channel> :<reason>",
    "comment"  => "Returned when attempting to set a mode on a channel which "
        . "does not support channel modes, or channel mode changes. "
        . "Also known as ERR_MODELESS",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_NEEDREGGEDNICK",
    "numeric"  => "477",
    "origin"   => "Bahamut, ircu, Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_BANLISTFULL",
    "numeric" => "478",
    "origin"  => "RFC2812",
    "format"  => "<channel> <char> :<reason>",
    "comment" => "Returned when a channel access list (i.e. ban list etc) "
        . "is full and cannot be added to",
];

$irc2numerics[] = [
    "name"    => "ERR_BADCHANNAME",
    "numeric" => "479",
    "origin"  => "Hybrid",
];

$irc2numerics[] = [
    "name"    => "ERR_LINKFAIL",
    "numeric" => "479",
    "origin"  => "Unreal",
];

$irc2numerics[] = [
    "name"     => "ERR_NOULINE",
    "numeric"  => "480",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_CANNOTKNOCK",
    "numeric"  => "480",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NOPRIVILEGES",
    "numeric" => "481",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by any command requiring special privileges "
        . "(eg. IRC operator) to indicate the operation was "
        . "unsuccessful",
];

$irc2numerics[] = [
    "name"    => "ERR_CHANOPRIVSNEEDED",
    "numeric" => "482",
    "origin"  => "RFC1459",
    "format"  => "<channel> :<reason>",
    "comment" => "Returned by any command requiring special channel "
        . "privileges (eg. channel operator) to indicate the "
        . "operation was unsuccessful",
];

$irc2numerics[] = [
    "name"    => "ERR_CANTKILLSERVER",
    "numeric" => "483",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by KILL to anyone who tries to kill a server",
];

$irc2numerics[] = [
    "name"     => "ERR_RESTRICTED",
    "numeric"  => "484",
    "origin"   => "RFC2812",
    "format"   => ":<reason>",
    "comment"  => "Sent by the server to a user upon connection to "
        . "indicate the restricted nature of the connection (i.e. "
        . "usermode +r)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ISCHANSERVICE",
    "numeric"  => "484",
    "origin"   => "Undernet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_DESYNC",
    "numeric"  => "484",
    "origin"   => "Bahamut, Hybrid, PTlink",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ATTACKDENY",
    "numeric"  => "484",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_UNIQOPRIVSNEEDED",
    "numeric" => "485",
    "origin"  => "RFC2812",
    "format"  => ":<reason>",
    "comment" => "Any mode requiring 'channel creator' privileges returns "
        . "this error if the client is attempting to use it while "
        . "not a channel creator on the given channel",
];

$irc2numerics[] = [
    "name"     => "ERR_KILLDENY",
    "numeric"  => "485",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_CANTKICKADMIN",
    "numeric"  => "485",
    "origin"   => "PTlink",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ISREALSERVICE",
    "numeric"  => "485",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_NONONREG",
    "numeric"  => "486",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_HTMDISABLED",
    "numeric"  => "486",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ACCOUNTONLY",
    "numeric"  => "486",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_CHANTOORECENT",
    "numeric"  => "487",
    "origin"   => "IRCnet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_MSGSERVICES",
    "numeric"  => "487",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_TSLESSCHAN",
    "numeric" => "488",
    "origin"  => "IRCnet",
];

$irc2numerics[] = [
    "name"     => "ERR_VOICENEEDED",
    "numeric"  => "489",
    "origin"   => "Undernet",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_SECUREONLYCHAN",
    "numeric"  => "489",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NOOPERHOST",
    "numeric" => "491",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by OPER to a client who cannot become an IRC "
        . "operator because the server has been configured to "
        . "disallow the client's host",
];

$irc2numerics[] = [
    "name"     => "ERR_NOSERVICEHOST",
    "numeric"  => "492",
    "origin"   => "RFC1459",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_NOFEATURE",
    "numeric" => "493",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_BADFEATURE",
    "numeric" => "494",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_BADLOGTYPE",
    "numeric" => "495",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_BADLOGSYS",
    "numeric" => "496",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_BADLOGVALUE",
    "numeric" => "497",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_ISOPERLCHAN",
    "numeric" => "498",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_CHANOWNPRIVNEEDED",
    "numeric" => "499",
    "origin"  => "Unreal",
    "comment" => "Works just like ERR_CHANOPRIVSNEEDED except it indicates "
        . "that owner status (+q) is needed.",
    "seealso" => "482",
];

$irc2numerics[] = [
    "name"    => "ERR_UMODEUNKNOWNFLAG",
    "numeric" => "501",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Returned by the server to indicate that a MODE message "
        . "was sent with a nickname parameter and that the mode "
        . "flag sent was not recognised",
];

$irc2numerics[] = [
    "name"    => "ERR_USERSDONTMATCH",
    "numeric" => "502",
    "origin"  => "RFC1459",
    "format"  => ":<reason>",
    "comment" => "Error sent to any user trying to view or change the "
        . "user mode for a user other than themselves",
];

$irc2numerics[] = [
    "name"    => "ERR_GHOSTEDCLIENT",
    "numeric" => "503",
    "origin"  => "Hybrid",
];

$irc2numerics[] = [
    "name"     => "ERR_VWORLDWARN",
    "numeric"  => "503",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "format"   => ":<warning_text>",
    "comment"  => "Warning about Virtual-World being turned off. Obsoleted "
        . "in favour for RPL_MODECHANGEWARN",
    "seealso"  => "662",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_USERNOTONSERV",
    "numeric" => "504",
];

$irc2numerics[] = [
    "name"    => "ERR_SILELISTFULL",
    "numeric" => "511",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYWATCH",
    "numeric" => "512",
    "origin"  => "Bahamut",
    "comment" => "Also known as ERR_NOTIFYFULL (aircd), I presume they are "
        . "the same",
];

$irc2numerics[] = [
    "name"         => "ERR_BADPING",
    "numeric"      => "513",
    "origin"       => "ircu",
    "comment"      => "Also known as ERR_NEEDPONG (Unreal/Ultimate) for use "
        . "during registration, however it's not used in Unreal "
        . "(and might not be used in Ultimate either).",
    "registration" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_INVALID_ERROR",
    "numeric"  => "514",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_TOOMANYDCC",
    "numeric"  => "514",
    "origin"   => "Bahamut (+ Unreal?)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_BADEXPIRE",
    "numeric" => "515",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_DONTCHEAT",
    "numeric" => "516",
    "origin"  => "ircu",
];

$irc2numerics[] = [
    "name"    => "ERR_DISABLED",
    "numeric" => "517",
    "origin"  => "ircu",
    "format"  => "<command> :<info/reason>",
];

$irc2numerics[] = [
    "name"     => "ERR_NOINVITE",
    "numeric"  => "518",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_LONGMASK",
    "numeric"  => "518",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_ADMONLY",
    "numeric"  => "519",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_TOOMANYUSERS",
    "numeric"  => "519",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_OPERONLY",
    "numeric"  => "520",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_MASKTOOWIDE",
    "numeric"  => "520",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_WHOTRUNC",
    "numeric"  => "520",
    "origin"   => "AustHex",
    "contact"  => "dev@austnet.org",
    "comment"  => "This is considered obsolete in favour of "
        . "ERR_TOOMANYMATCHES, and should no longer be used.",
    "seealso"  => "416",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_LISTSYNTAX",
    "numeric"  => "521",
    "origin"   => "Bahamut",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "ERR_WHOSYNTAX",
    "numeric" => "522",
    "origin"  => "Bahamut",
];

$irc2numerics[] = [
    "name"    => "ERR_WHOLIMEXCEED",
    "numeric" => "523",
    "origin"  => "Bahamut",
];

$irc2numerics[] = [
    "name"     => "ERR_QUARANTINED",
    "numeric"  => "524",
    "origin"   => "ircu",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "ERR_OPERSPVERIFY",
    "numeric"  => "524",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"        => "ERR_REMOTEPFX",
    "numeric"     => "525",
    "origin"      => "CAPAB USERCMDPFX",
    "contact"     => "ejb@hades.skumler.net",
    "information" => "http://www.hades.skumler.net/~ejb/draft-brocklesby-irc-usercmdpfx-00.txt",
    "format"      => "<nickname> :<reason>",
    "comment"     => "Proposed.",
];

$irc2numerics[] = [
    "name"        => "ERR_PFXUNROUTABLE",
    "numeric"     => "526",
    "origin"      => "CAPAB USERCMDPFX",
    "contact"     => "ejb@hades.skumler.net",
    "information" => "http://www.hades.skumler.net/~ejb/draft-brocklesby-irc-usercmdpfx-00.txt",
    "format"      => "<nickname> :<reason>",
    "comment"     => "Proposed.",
];

$irc2numerics[] = [
    "name"    => "ERR_BADHOSTMASK",
    "numeric" => "550",
    "origin"  => "QuakeNet",
];

$irc2numerics[] = [
    "name"    => "ERR_HOSTUNAVAIL",
    "numeric" => "551",
    "origin"  => "QuakeNet",
];

$irc2numerics[] = [
    "name"    => "ERR_USINGSLINE",
    "numeric" => "552",
    "origin"  => "QuakeNet",
];

$irc2numerics[] = [
    "name"     => "ERR_STATSSLINE",
    "numeric"  => "553",
    "origin"   => "QuakeNet",
    "conflict" => "yes",
];


$irc2numerics[] = "600-899, reply from server commands";

$irc2numerics[] = [
    "name"    => "RPL_LOGON",
    "numeric" => "600",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_LOGOFF",
    "numeric" => "601",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_WATCHOFF",
    "numeric" => "602",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_WATCHSTAT",
    "numeric" => "603",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_NOWON",
    "numeric" => "604",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_NOWOFF",
    "numeric" => "605",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_WATCHLIST",
    "numeric" => "606",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFWATCHLIST",
    "numeric" => "607",
    "origin"  => "Bahamut, Unreal",
];

$irc2numerics[] = [
    "name"    => "RPL_WATCHCLEAR",
    "numeric" => "608",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPMORE",
    "numeric"  => "610",
    "origin"   => "Unreal",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ISOPER",
    "numeric"  => "610",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_ISLOCOP",
    "numeric" => "611",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_ISNOTOPER",
    "numeric" => "612",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFISOPER",
    "numeric" => "613",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPMORE",
    "numeric"  => "615",
    "origin"   => "PTlink",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISMODES",
    "numeric"  => "615",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISHOST",
    "numeric"  => "616",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_DCCSTATUS",
    "numeric"  => "617",
    "origin"   => "Bahamut ( + Unreal?)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOISBOT",
    "numeric"  => "617",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_DCCLIST",
    "numeric" => "618",
    "origin"  => "Bahamut (+ Unreal?)",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFDCCLIST",
    "numeric"  => "619",
    "origin"   => "Bahamut (+ Unreal?)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_WHOWASHOST",
    "numeric"  => "619",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_DCCINFO",
    "numeric"  => "620",
    "origin"   => "Bahamut (+ Unreal?)",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_RULESSTART",
    "numeric"  => "620",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_RULES",
    "numeric"  => "621",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_ENDOFRULES",
    "numeric"  => "622",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_MAPMORE",
    "numeric"  => "623",
    "origin"   => "Ultimate",
    "conflict" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_OMOTDSTART",
    "numeric" => "624",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_OMOTD",
    "numeric" => "625",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFOMOTD",
    "numeric" => "626",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_SETTINGS",
    "numeric" => "630",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFSETTINGS",
    "numeric" => "631",
    "origin"  => "Ultimate",
];

$irc2numerics[] = [
    "name"     => "RPL_DUMPING",
    "numeric"  => "640",
    "origin"   => "Unreal",
    "comment"  => "Never actually used by Unreal - was defined however the "
        . "feature that would have used this numeric was never "
        . "created.",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_DUMPRPL",
    "numeric"  => "641",
    "origin"   => "Unreal",
    "comment"  => "Never actually used by Unreal - was defined however the "
        . "feature that would have used this numeric was never "
        . "created.",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"     => "RPL_EODUMP",
    "numeric"  => "642",
    "origin"   => "Unreal",
    "comment"  => "Never actually used by Unreal - was defined however the "
        . "feature that would have used this numeric was never "
        . "created.",
    "obsolete" => "yes",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEROUTE_HOP",
    "numeric" => "660",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<target> <hop#> [<address> [<hostname> | '*'] <usec_ping>]",
    "comment" => "Returned from the TRACEROUTE IRC-Op command when "
        . "tracerouting a host",
];

$irc2numerics[] = [
    "name"    => "RPL_TRACEROUTE_START",
    "numeric" => "661",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<target> <target_FQDN> <target_address> <max_hops>",
    "comment" => "Start of an RPL_TRACEROUTE_HOP list",
];

$irc2numerics[] = [
    "name"    => "RPL_MODECHANGEWARN",
    "numeric" => "662",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "['+' | '-']<mode_char> :<warning>",
    "comment" => "Plain text warning to the user about turning on or off a "
        . "user mode. If no '+' or '-' prefix is used for the mode "
        . "char, '+' is presumed.",
];

$irc2numerics[] = [
    "name"    => "RPL_CHANREDIR",
    "numeric" => "663",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<old_chan> <new_chan> :<info>",
    "comment" => "Used to notify the client upon JOIN that they are joining "
        . "a different channel than expected because the IRC Daemon "
        . "has been set up to map the channel they attempted to "
        . "join to the channel they eventually will join.",
];

$irc2numerics[] = [
    "name"    => "RPL_SERVMODEIS",
    "numeric" => "664",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<server> <modes> <parameters>..",
    "comment" => "Reply to MODE <servername>. KineIRCd supports server "
        . "modes to simplify configuration of servers; Similar to RPL_CHANNELMODEIS",
];

$irc2numerics[] = [
    "name"    => "RPL_OTHERUMODEIS",
    "numeric" => "665",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<nickname> <modes>",
    "comment" => "Reply to MODE <nickname> to return the user-modes of "
        . "another user to help troubleshoot connections, etc. "
        . "Similar to RPL_UMODEIS, however including the target",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOF_GENERIC",
    "numeric" => "666",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<command> [<parameter> ...] :<info>",
    "comment" => "Generic response for new lists to save numerics.",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOWASDETAILS",
    "numeric" => "670",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<nick> <type> :<information>",
    "comment" => "Returned by WHOWAS to return extended information (if "
        . "available). The type field is a number indication what "
        . "kind of information.",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISSECURE",
    "numeric" => "671",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<nick> <type> [:<info>]",
    "comment" => "Reply to WHOIS command - Returned if the target is "
        . "connected securely, eg. type may be TLSv1, or SSLv2 etc. "
        . "If the type is unknown, a '*' may be used.",
];

$irc2numerics[] = [
    "name"    => "RPL_UNKNOWNMODES",
    "numeric" => "672",
    "origin"  => "Ithildin",
    "contact" => "wd@telekinesis.org",
    "format"  => "<modes> :<info>",
    "comment" => "Returns a full list of modes that are unknown when a "
        . "client issues a MODE command (rather than one numeric per "
        . "mode)",
];

$irc2numerics[] = [
    "name"    => "RPL_CANNOTSETMODES",
    "numeric" => "673",
    "origin"  => "Ithildin",
    "contact" => "wd@telekinesis.org",
    "format"  => "<modes> :<info>",
    "comment" => "Returns a full list of modes that cannot be set when a "
        . "client issues a MODE command",
];

$irc2numerics[] = [
    "name"    => "RPL_LUSERSTAFF",
    "numeric" => "678",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<staff_online_count> :<info>",
    "comment" => "Reply to LUSERS command - Number of network staff (or "
        . "'helpers') online (differs from Local/Global operators). "
        . "Similar format to RPL_LUSEROP",
];

$irc2numerics[] = [
    "name"    => "RPL_TIMEONSERVERIS",
    "numeric" => "679",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<seconds> [<nanoseconds> | '0'] <timezone> <flags> :<info>",
    "comment" => "Optionally sent upon connection, and/or sent as a reply to "
        . "the TIME command. This returns the time on the server in "
        . "a uniform manner. The seconds (and optionally nanoseconds) "
        . "is the time since the UNIX Epoch, and is used since many "
        . "existing timestamps in the IRC-2 protocol are done this "
        . "way (i.e. ban lists). The timezone is hours and minutes "
        . "each of Greenwich ('[+/-]HHMM'). Since all timestamps sent "
        . "from the server are in a similar format, this numeric is "
        . "designed to give clients the ability to provide accurate "
        . "timestamps to their users.",
];

$irc2numerics[] = [
    "name"        => "RPL_NETWORKS",
    "numeric"     => "682",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/IIRC?rev=HEAD",
    "format"      => "<name> <through_name> <hops> :<info>",
    "comment"     => "A reply to the NETWORKS command when requesting a list of "
        . "known networks (within the IIRC domain).",
];

$irc2numerics[] = [
    "name"        => "RPL_YOURLANGUAGEIS",
    "numeric"     => "687",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD",
    "format"      => "<code(s)> :<info>",
    "comment"     => "Reply to the LANGUAGE command, informing the client of "
        . "the language(s) it has set",
];

$irc2numerics[] = [
    "name"        => "RPL_LANGUAGE",
    "numeric"     => "688",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD",
    "format"      => "<code> <revision> <maintainer> <flags> * :<info>",
    "comment"     => "A language reply to LANGUAGE when requesting a list of "
        . "known languages",
];

$irc2numerics[] = [
    "name"    => "RPL_WHOISSTAFF",
    "numeric" => "689",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => ":<info>",
    "comment" => "The user is a staff member. The information may explain "
        . "the user's job role, or simply state that they are a part "
        . "of the network staff. Staff members are not IRC operators, "
        . "but rather people who have special access in association "
        . "with network services. KineIRCd uses this numeric instead "
        . "of the existing numerics due to the overwhelming number of "
        . "conflicts.",
];

$irc2numerics[] = [
    "name"        => "RPL_WHOISLANGUAGE",
    "numeric"     => "690",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD",
    "format"      => "<nick> <language codes>",
    "comment"     => "Reply to WHOIS command - A list of languages someone can "
        . "speak. The language codes are comma delimitered.",
];


$irc2numerics[] = [
    "name"    => "RPL_MODLIST",
    "numeric" => "702",
    "origin"  => "RatBox",
    "format"  => "<?> 0x<?> <?> <?>",
    "comment" => "Output from the MODLIST command",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFMODLIST",
    "numeric" => "703",
    "origin"  => "RatBox",
    "format"  => ":<text>",
    "comment" => "Terminates MODLIST output",
];

$irc2numerics[] = [
    "name"    => "RPL_HELPSTART",
    "numeric" => "704",
    "origin"  => "RatBox",
    "format"  => "<command> :<text>",
    "comment" => "Start of HELP command output",
];

$irc2numerics[] = [
    "name"    => "RPL_HELPTXT",
    "numeric" => "705",
    "origin"  => "RatBox",
    "format"  => "<command> :<text>",
    "comment" => "Output from HELP command",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFHELP",
    "numeric" => "706",
    "origin"  => "RatBox",
    "format"  => "<command> :<text>",
    "comment" => "End of HELP command output",
];

$irc2numerics[] = [
    "name"    => "RPL_ETRACEFULL",
    "numeric" => "708",
    "origin"  => "RatBox",
    "format"  => "<?> <?> <?> <?> <?> <?> <?> :<?>",
    "comment" => "Output from 'extended' trace",
];

$irc2numerics[] = [
    "name"    => "RPL_ETRACE",
    "numeric" => "709",
    "origin"  => "RatBox",
    "format"  => "<?> <?> <?> <?> <?> <?> :<?>",
    "comment" => "Output from 'extended' trace",
];

$irc2numerics[] = [
    "name"    => "RPL_KNOCK",
    "numeric" => "710",
    "origin"  => "RatBox",
    "format"  => "<channel> <nick>!<user>@<host> :<text>",
    "comment" => "Message delivered using KNOCK command",
];

$irc2numerics[] = [
    "name"    => "RPL_KNOCKDLVR",
    "numeric" => "711",
    "origin"  => "RatBox",
    "format"  => "<channel> :<text>",
    "comment" => "Message returned from using KNOCK command",
];

$irc2numerics[] = [
    "name"    => "ERR_TOOMANYKNOCK",
    "numeric" => "712",
    "origin"  => "RatBox",
    "format"  => "<channel> :<text>",
    "comment" => "Message returned when too many KNOCKs for a channel have "
        . "been sent by a user",
];

$irc2numerics[] = [
    "name"    => "ERR_CHANOPEN",
    "numeric" => "713",
    "origin"  => "RatBox",
    "format"  => "<channel> :<text>",
    "comment" => "Message returned from KNOCK when the channel can be freely "
        . "joined by the user",
];

$irc2numerics[] = [
    "name"    => "ERR_KNOCKONCHAN",
    "numeric" => "714",
    "origin"  => "RatBox",
    "format"  => "<channel> :<text>",
    "comment" => "Message returned from KNOCK when the user has used KNOCK "
        . "on a channel they have already joined",
];

$irc2numerics[] = [
    "name"    => "ERR_KNOCKDISABLED",
    "numeric" => "715",
    "origin"  => "RatBox",
    "format"  => ":<text>",
    "comment" => "Returned from KNOCK when the command has been disabled",
];

$irc2numerics[] = [
    "name"    => "RPL_TARGUMODEG",
    "numeric" => "716",
    "origin"  => "RatBox",
    "format"  => "<nick> :<info>",
    "comment" => "Sent to indicate the given target is set +g (server-side "
        . "ignore)",
];

$irc2numerics[] = [
    "name"    => "RPL_TARGNOTIFY",
    "numeric" => "717",
    "origin"  => "RatBox",
    "format"  => "<nick> :<info>",
    "comment" => "Sent following a PRIVMSG/NOTICE to indicate the target "
        . "has been notified of an attempt to talk to them while "
        . "they are set +g",
];

$irc2numerics[] = [
    "name"    => "RPL_UMODEGMSG",
    "numeric" => "718",
    "origin"  => "RatBox",
    "format"  => "<nick> <user>@<host> :<info>",
    "comment" => "Sent to a user who is +g to inform them that someone has "
        . "attempted to talk to them (via PRIVMSG/NOTICE), and that "
        . "they will need to be accepted (via the ACCEPT command) "
        . "before being able to talk to them",
];

$irc2numerics[] = [
    "name"    => "RPL_OMOTDSTART",
    "numeric" => "720",
    "origin"  => "RatBox",
    "format"  => ":<text>",
    "comment" => "IRC Operator MOTD header, sent upon OPER command",
];

$irc2numerics[] = [
    "name"    => "RPL_OMOTD",
    "numeric" => "721",
    "origin"  => "RatBox",
    "format"  => ":<text>",
    "comment" => "IRC Operator MOTD text (repeated, usually)",
];

$irc2numerics[] = [
    "name"    => "RPL_ENDOFOMOTD",
    "numeric" => "722",
    "origin"  => "RatBox",
    "format"  => ":<text>",
    "comment" => "IRC operator MOTD footer",
];

$irc2numerics[] = [
    "name"    => "ERR_NOPRIVS",
    "numeric" => "723",
    "origin"  => "RatBox",
    "format"  => "<command> :<text>",
    "comment" => "Returned from an oper command when the IRC operator "
        . "does not have the relevant operator privileges.",
];

$irc2numerics[] = [
    "name"    => "RPL_TESTMARK",
    "numeric" => "724",
    "origin"  => "RatBox",
    "format"  => "<nick>!<user>@<host> <?> <?> :<text>",
    "comment" => "Reply from an oper command reporting how many users "
        . "match a given user@host mask",
];

$irc2numerics[] = [
    "name"    => "RPL_TESTLINE",
    "numeric" => "725",
    "origin"  => "RatBox",
    "format"  => "<?> <?> <?> :<?>",
    "comment" => "Reply from an oper command reporting relevant I/K lines "
        . "that will match a given user@host",
];

$irc2numerics[] = [
    "name"    => "RPL_NOTESTLINE",
    "numeric" => "726",
    "origin"  => "RatBox",
    "format"  => "<?> :<text>",
    "comment" => "Reply from oper command reporting no I/K lines match "
        . "the given user@host",
];

$irc2numerics[] = [
    "name"    => "RPL_CHALLENGE_TEXT",
    "numeric" => "740",
    "origin"  => "RatBox",
    "comment" => "Displays CHALLENGE text",
];

$irc2numerics[] = [
    "name"    => "RPL_CHALLENGE_END",
    "numeric" => "741",
    "origin"  => "RatBox",
    "comment" => "End of CHALLENGE numeric",
];

$irc2numerics[] = [
    "name"    => "RPL_XINFO",
    "numeric" => "771",
    "origin"  => "Ithildin",
    "contact" => "wd@telekinesis.org",
    "comment" => "Used to send 'eXtended info' to the client, a "
        . "replacement for the STATS command to send a large "
        . "variety of data and minimise numeric pollution.",
];

$irc2numerics[] = [
    "name"    => "RPL_XINFOSTART",
    "numeric" => "773",
    "origin"  => "Ithildin",
    "contact" => "wd@telekinesis.org",
    "comment" => "Start of an RPL_XINFO list",
];

$irc2numerics[] = [
    "name"    => "RPL_XINFOEND",
    "numeric" => "774",
    "origin"  => "Ithildin",
    "contact" => "wd@telekinesis.org",
    "comment" => "Termination of an RPL_XINFO list",
];


$irc2numerics[] = "900-999, errors (usually)";

$irc2numerics[] = [
    "name"    => "RPL_SASL",
    "numeric" => "903",
    "origin"  => "charybdis",
    "comment" => "Authentication via SASL successful.",
];

$irc2numerics[] = [
    "name"    => "ERR_SASL",
    "numeric" => "904",
    "origin"  => "charybdis",
    "comment" => "Authentication via SASL unsuccessful.",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTDOCOMMAND",
    "numeric" => "972",
    "origin"  => "Unreal",
    "comment" => "Works similarly to all of KineIRCd's CANNOT* numerics. "
        . "This one indicates that a command could not be performed "
        . "for an arbitrary reason. For example, a halfop trying to "
        . "kick an op.",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTCHANGEUMODE",
    "numeric" => "973",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<mode_char> :<reason>",
    "comment" => "Reply to MODE when a user cannot change a user mode",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTCHANGECHANMODE",
    "numeric" => "974",
    "origin"  => "KineIRCd (+ Unreal?)",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<mode_char> :<reason>",
    "comment" => "Reply to MODE when a user cannot change a channel mode",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTCHANGESERVERMODE",
    "numeric" => "975",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<mode_char> :<reason>",
    "comment" => "Reply to MODE when a user cannot change a server mode",
];

$irc2numerics[] = [
    "name"    => "ERR_CANNOTSENDTONICK",
    "numeric" => "976",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<nick> :<reason>",
    "comment" => "Returned from NOTICE, PRIVMSG or other commands to notify "
        . "the user that they cannot send a message to a particular "
        . "client. Similar to ERR_CANNOTSENDTOCHAN. KineIRCd uses "
        . "this in conjunction with user-mode +R to allow users to "
        . "block people who are not identified to services (spam "
        . "avoidance)",
];

$irc2numerics[] = [
    "name"    => "ERR_UNKNOWNSERVERMODE",
    "numeric" => "977",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<modechar> :<info>",
    "comment" => "Returned by MODE to inform the client they used an "
        . "unknown server mode character.",
];

$irc2numerics[] = [
    "name"    => "ERR_SERVERMODELOCK",
    "numeric" => "979",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<target> :<info>",
    "comment" => "Returned by MODE to inform the client the server has "
        . "been set mode +L by an administrator to stop server "
        . "modes being changed",
];

$irc2numerics[] = [
    "name"    => "ERR_BADCHARENCODING",
    "numeric" => "980",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<command> <charset> :<info>",
    "comment" => "Returned by any command which may have had the given data "
        . "modified because one or more glyphs were incorrectly "
        . "encoded in the current charset (given). Such a use would "
        . "be where an invalid UTF-8 sequence was given which may be "
        . "considered insecure, or defines a character which is "
        . "invalid within that context. For safety reasons, the "
        . "invalid character is not returned to the client.",
];

$irc2numerics[] = [
    "name"        => "ERR_TOOMANYLANGUAGES",
    "numeric"     => "981",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD",
    "format"      => "<max_langs> :<info>",
    "comment"     => "Returned by the LANGUAGE command to tell the client they "
        . "cannot set as many languages as they have requested. "
        . "To assist the client, the maximum languages which can be "
        . "set at one time is given, and the language settings are "
        . "not changed.",
];

$irc2numerics[] = [
    "name"        => "ERR_NOLANGUAGE",
    "numeric"     => "982",
    "origin"      => "KineIRCd",
    "contact"     => "kineircd@alien.net.au",
    "information" => "http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/kineircd/kineircd/doc/LANGUAGE?rev=HEAD",
    "format"      => "<language_code> :<info>",
    "comment"     => "Returned by the LANGUAGE command to tell the client it "
        . "has specified an unknown language code.",
];

$irc2numerics[] = [
    "name"    => "ERR_TEXTTOOSHORT",
    "numeric" => "983",
    "origin"  => "KineIRCd",
    "contact" => "kineircd@alien.net.au",
    "format"  => "<command> :<info>",
    "comment" => "Returned by any command requiring text (such as a message "
        . "or a reason), which was not long enough to be considered "
        . "valid. This was created initially to combat '/wallops foo' "
        . "abuse, but is also used by DIE and RESTART commands to "
        . "attempt to encourage meaningful reasons.",
];

$irc2numerics[] = [
    "name"    => "ERR_NUMERIC_ERR",
    "numeric" => "999",
    "origin"  => "Bahamut",
    "comment" => "Also known as ERR_NUMERICERR (Unreal)",
];

$ircNumericCodes = [
    '001' => 'RPL_WELCOME',
    '002' => 'RPL_YOURHOST',
    '003' => 'RPL_CREATED',
    '004' => 'RPL_MYINFO',
    // RPL_BOUNCE is deprecated however we handle it gracefully by using the registration status as context
    '005' => 'RPL_BOUNCE_OR_RPL_ISUPPORT',
    // 006 may be RPL_MAP, usage not known
    // 007 may be RPL_MAPEND, usage not known
    '010' => 'RPL_BOUNCE', // Modern RPL_BOUNCE
    '302' => 'RPL_USERHOST',
    '303' => 'RPL_ISON',
    '301' => 'RPL_AWAY',
    '305' => 'RPL_UNAWAY',
    '306' => 'RPL_NOWAWAY',
    '311' => 'RPL_WHOISUSER',
    '312' => 'RPL_WHOISSERVER',
    '313' => 'RPL_WHOISOPERATOR',
    '317' => 'RPL_WHOISIDLE',
    '318' => 'RPL_ENDOFWHOIS',
    '319' => 'RPL_WHOISCHANNELS',
    '314' => 'RPL_WHOWASUSER',
    '369' => 'RPL_ENDOFWHOWAS',
    '321' => 'RPL_LISTSTART',
    '322' => 'RPL_LIST',
    '323' => 'RPL_LISTEND',
    '325' => 'RPL_UNIQOPIS',
    '324' => 'RPL_CHANNELMODEIS',
    '331' => 'RPL_NOTOPIC',
    '332' => 'RPL_TOPIC',
    '341' => 'RPL_INVITING',
    '342' => 'RPL_SUMMONING',
    '346' => 'RPL_INVITELIST',
    '347' => 'RPL_ENDOFINVITELIST',
    '348' => 'RPL_EXCEPTLIST',
    '349' => 'RPL_ENDOFEXCEPTLIST',
    '351' => 'RPL_VERSION',
    '352' => 'RPL_WHOREPLY',
    '315' => 'RPL_ENDOFWHO',
    '353' => 'RPL_NAMREPLY',
    '366' => 'RPL_ENDOFNAMES',
    '364' => 'RPL_LINKS',
    '365' => 'RPL_ENDOFLINKS',
    '367' => 'RPL_BANLIST',
    '368' => 'RPL_ENDOFBANLIST',
    '371' => 'RPL_INFO',
    '374' => 'RPL_ENDOFINFO',
    '375' => 'RPL_MOTDSTART',
    '372' => 'RPL_MOTD',
    '376' => 'RPL_ENDOFMOTD',
    '381' => 'RPL_YOUREOPER',
    '382' => 'RPL_REHASHING',
    '383' => 'RPL_YOURESERVICE',
    '391' => 'RPL_TIME',
    '392' => 'RPL_USERSSTART',
    '393' => 'RPL_USERS',
    '394' => 'RPL_ENDOFUSERS',
    '395' => 'RPL_NOUSERS',
    '200' => 'RPL_TRACELINK',
    '201' => 'RPL_TRACECONNECTING',
    '202' => 'RPL_TRACEHANDSHAKE',
    '203' => 'RPL_TRACEUNKNOWN',
    '204' => 'RPL_TRACEOPERATOR',
    '205' => 'RPL_TRACEUSER',
    '206' => 'RPL_TRACESERVER',
    '207' => 'RPL_TRACESERVICE',
    '208' => 'RPL_TRACENEWTYPE',
    '209' => 'RPL_TRACECLASS',
    '210' => 'RPL_TRACERECONNECT',
    '261' => 'RPL_TRACELOG',
    '262' => 'RPL_TRACEEND',
    '265' => 'RPL_LOCALUSERS',
    '266' => 'RPL_GLOBALUSERS',
    '211' => 'RPL_STATSLINKINFO',
    '212' => 'RPL_STATSCOMMANDS',
    '219' => 'RPL_ENDOFSTATS',
    '242' => 'RPL_STATSUPTIME',
    '243' => 'RPL_STATSOLINE',
    '221' => 'RPL_UMODEIS',
    '234' => 'RPL_SERVLIST',
    '235' => 'RPL_SERVLISTEND',
    '251' => 'RPL_LUSERCLIENT',
    '252' => 'RPL_LUSEROP',
    '253' => 'RPL_LUSERUNKNOWN',
    '254' => 'RPL_LUSERCHANNELS',
    '255' => 'RPL_LUSERME',
    '256' => 'RPL_ADMINME',
    '259' => 'RPL_ADMINEMAIL',
    '263' => 'RPL_TRYAGAIN',
    '328' => 'RPL_CHANNEL_URL', // Non-standard
    '329' => 'RPL_CREATIONTIME', // Non-standard
    '333' => 'RPL_TOPICWHOTIME', // Non-standard ircu
    '354' => 'RPL_WHOSPCRPL', // Non-standard WHOX replies
    // '378' => 'RPL_WHOISHOST', // @todo RPL_BANEXPIRED RPL_WHOISHOST RPL_MOTD, RPL_WHOISHOST seems most frequent, needs context
    '392' => 'RPL_USERSSTART',
    '393' => 'RPL_USERS	RFC1459',
    '394' => 'RPL_ENDOFUSERS',
    '395' => 'RPL_NOUSERS',
    '396' => 'RPL_HOSTHIDDEN', // +x was successful, Non-standard
    '401' => 'ERR_NOSUCHNICK',
    '402' => 'ERR_NOSUCHSERVER',
    '403' => 'ERR_NOSUCHCHANNEL',
    '404' => 'ERR_CANNOTSENDTOCHAN',
    '405' => 'ERR_TOOMANYCHANNELS',
    '406' => 'ERR_WASNOSUCHNICK',
    '407' => 'ERR_TOOMANYTARGETS',
    '408' => 'ERR_NOSUCHSERVICE',
    '409' => 'ERR_NOORIGIN',
    '410' => 'ERR_INVALIDCAPCMD', // IETF draft-mitchell-irc-capabilities-01
    '411' => 'ERR_NORECIPIENT',
    '412' => 'ERR_NOTEXTTOSEND',
    '413' => 'ERR_NOTOPLEVEL',
    '414' => 'ERR_WILDTOPLEVEL',
    '415' => 'ERR_BADMASK',
    '421' => 'ERR_UNKNOWNCOMMAND',
    '422' => 'ERR_NOMOTD',
    '423' => 'ERR_NOADMININFO',
    '424' => 'ERR_FILEERROR',
    '431' => 'ERR_NONICKNAMEGIVEN',
    '432' => 'ERR_ERRONEUSNICKNAME',
    '433' => 'ERR_NICKNAMEINUSE',
    '436' => 'ERR_NICKCOLLISION',
    '437' => 'ERR_UNAVAILRESOURCE',
    '441' => 'ERR_USERNOTINCHANNEL',
    '442' => 'ERR_NOTONCHANNEL',
    '443' => 'ERR_USERONCHANNEL',
    '444' => 'ERR_NOLOGIN',
    '445' => 'ERR_SUMMONDISABLED',
    '446' => 'ERR_USERSDISABLED',
    '451' => 'ERR_NOTREGISTERED',
    '461' => 'ERR_NEEDMOREPARAMS',
    '462' => 'ERR_ALREADYREGISTRED',
    '463' => 'ERR_NOPERMFORHOST',
    '464' => 'ERR_PASSWDMISMATCH',
    '465' => 'ERR_YOUREBANNEDCREEP',
    '466' => 'ERR_YOUWILLBEBANNED',
    '467' => 'ERR_KEYSET',
    '471' => 'ERR_CHANNELISFULL',
    '472' => 'ERR_UNKNOWNMODE',
    '473' => 'ERR_INVITEONLYCHAN',
    '474' => 'ERR_BANNEDFROMCHAN',
    '475' => 'ERR_BADCHANNELKEY',
    '476' => 'ERR_BADCHANMASK',
    '477' => 'ERR_NOCHANMODES',
    '478' => 'ERR_BANLISTFULL',
    '481' => 'ERR_NOPRIVILEGES',
    '482' => 'ERR_CHANOPRIVSNEEDED',
    '483' => 'ERR_CANTKILLSERVER',
    '484' => 'ERR_RESTRICTED',
    '485' => 'ERR_UNIQOPPRIVSNEEDED',
    '491' => 'ERR_NOOPERHOST',
    '501' => 'ERR_UMODEUNKNOWNFLAG',
    '502' => 'ERR_USERSDONTMATCH',
    /* These are reserved for future use, deprecated, etc */
    '231' => 'RPL_SERVICEINFO',
    '232' => 'RPL_ENDOFSERVICES',
    '233' => 'RPL_SERVICE',
    '300' => 'RPL_NONE',
    '316' => 'RPL_WHOISCHANOP',
    '361' => 'RPL_KILLDONE',
    '362' => 'RPL_CLOSING',
    '363' => 'RPL_CLOSEEND',
    '373' => 'RPL_INFOSTART',
    '384' => 'RPL_MYPORTIS',
    '213' => 'RPL_STATSCLINE',
    '214' => 'RPL_STATSNLINE',
    '215' => 'RPL_STATSILINE',
    '216' => 'RPL_STATSKLINE',
    '217' => 'RPL_STATSQLINE',
    '218' => 'RPL_STATSYLINE',
    '240' => 'RPL_STATSVLINE',
    '241' => 'RPL_STATSLLINE',
    '244' => 'RPL_STATSSLINE',
    '246' => 'RPL_STATSPING',
    '247' => 'RPL_STATSBLINE',
    '250' => 'RPL_STATSDLINE',
    '492' => 'ERR_NOSERVICEHOST',
];

$allNumerics_1 = array_keys($ircNumericCodes);


$allNumerics_2 = array_map(function ($x) {
    if(is_array($x)) {
        return (string) str_pad($x['numeric'], 3, '0', STR_PAD_LEFT);
    } else {
        return false;
    }
}, $irc2numerics);


$allCombined = array_merge($allNumerics_1, $allNumerics_2);
$allCombined = array_unique($allCombined);
sort($allCombined, SORT_NUMERIC);


echo "<?php\n";
echo "// New List: " . count($allNumerics_1) . "\n";
echo "// Old List: " . count($allNumerics_2) . "\n";
echo "// Combined List: " . count($allCombined) . "\n";

$reinsert = [];

foreach ($allCombined as $numeric) {
    if($numeric !== false) {
        $found = false;
        foreach ($irc2numerics as $code) {
            if(is_array($code) && $numeric == str_pad($code['numeric'], 3, '0', STR_PAD_LEFT)) {
                $found = true;
                break;
            }
        }

        if($found == false) {
            echo "// *** $numeric was not found in irc2numeric side of array.  You will need to re-insert it manually.\n";
            $reinsert = [$numeric, $ircNumericCodes[$numeric]];
        }
    }
}

echo "// ----------------\n";

if($format == 'variables') {
    echo "\$numerics = [\n";
} else {
    echo "class numericReplyBlob {\n";
}

foreach ($irc2numerics as $numeric) {
    if(!is_array($numeric)) {

        if($format == 'variables') {
            echo "\n" . "/*\n * Section " . $numeric . "\n */\n\n";
        } else {
            echo "" . "    /*\n     * Section " . $numeric . "\n     */\n\n";
        }

    } else {
        $comment = '';
        if(isset($numeric['name'])) {
            $comment .= str_pad($numeric['numeric'], 3, '0', STR_PAD_LEFT) . " " . $numeric['name'] . ". ";
        }
        if(isset($numeric['registration'])) {
            $comment .= 'Could be used during registration. ';
        }
        if(isset($numeric['obsolete'])) {
            $comment .= 'Obsolete. ';
        }
        if(isset($numeric['conflict'])) {
            $comment .= 'Conflicting. ';
        }
        if(isset($numeric['repeated'])) {
            $comment .= 'Multiple responses mapped. ';
        }
        if(isset($numeric['origin'])) {
            $comment .= 'Originated from ' . $numeric['origin'] . '. ';
        }
        if(isset($numeric['when'])) {
            $comment .= ', around ' . $numeric['when'] . '. ';
        }

        $comment .= "\n";
        if(isset($numeric['seealso'])) {
            $comment .= 'See also command ' . $numeric['seealso'] . '. ';
        }
        if(isset($numeric['information'])) {
            $comment .= 'More Info ' . $numeric['information'];
        }

        $comment = trim($comment);


        if(isset($numeric['comment'])) {
            $comment .= "\n" . str_replace("\n", " ", $numeric['comment']);
        }
        if(isset($numeric['format'])) {
            $comment .= "\n" . $numeric['format'];
        }

        $comment .= "\n\n@param  \StdClass \$line A partially pre-parsed IRC protocol line\n@return \StdClass       A StdClass that has additional information parsed, if available.";


        $comment = str_replace("\n", "\n     * ", "    /**  \n" . $comment) . "\n     */\n";


        $shortComment = '';
        if(isset($numeric['obsolete'])) {
            $shortComment .= 'Deprecated. ';
        }
        if(isset($numeric['registration'])) {
            $shortComment .= 'Registration. ';
        }
        if(isset($numeric['origin'])) {
            $shortComment .= $numeric['origin'] . '.';
        }

        if(isset($numeric['registration'])) {
            $comment .= 'Registration. ';
        }


        if($format == 'functions') {
            echo $comment;
        }

        $numeric['numeric'] = str_pad($numeric['numeric'], 3, '0', STR_PAD_LEFT);
        //echo "'{$numeric['numeric']}' => [\n";
        if($format == 'variables') {

            if(empty($ircNumericCodes[$numeric['numeric']])) {
                echo '// ';
            } elseif($ircNumericCodes[$numeric['numeric']] != $numeric['name']) {
                echo '// [' . $numeric['numeric'] . '] Original: ' . $ircNumericCodes[$numeric['numeric']] . ']' . "\n";
            }

            echo "'{$numeric['numeric']}' => ";
        }
        foreach ($numeric as $nKey => $nVal) {
            /* if($nKey == 'name' || $nKey == 'format' || $nKey == 'comment') {
                $nVal = str_replace("'", "\'", $nVal);
                echo "    '$nKey' => '$nVal', \n";
            } */

            if($format == 'variables') {
                if($nKey == 'name') {
                    echo "'$nVal',";
                    if(!empty($shortComment)) {
                        echo " // $shortComment";
                    }
                    echo "\n";
                }
            } else {
                if($nKey == 'name') {
                    $functionName = 'on_' . strtolower($nVal);

                    echo "    private function $functionName(\StdClass \$line) { \n        return \$line; \n    }\n\n";
                }
            }

        }
        // echo "], \n";
    }

}

if($format == 'variables') {
    echo "\n];\n";
} else {
    echo "}\n";
}
