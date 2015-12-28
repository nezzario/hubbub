<?php
/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2013-2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub\IRC;

use Hubbub\Utility;
use StdClass;

/**
 * Class Client
 * @package Hubbub\IRC
 */
class Client implements \Hubbub\Protocol\Client, \Hubbub\Iterable {
    use Parser;
    use Senders;

    /**
     * @var \Hubbub\Net\Client
     */
    protected $net;
    protected $logger, $bus, $conf;

    protected $protocol = 'irc';
    protected $state = 'disconnected';

    protected $recvBuffer = ''; // Where incomplete, fragmented commands go for temporary storage

    public $serverList = [];
    public $serverInfo = [];
    public $cfg = [
        'nickname' => 'Hubbub-Z',
        'username' => 'Hubbub',
        'realname' => 'Bob Marley',
    ];

    protected $currentServer, $currentPort;
    protected $currentServerIdx = 0;
    protected $nextAction;
    protected $nextActionTime = 0;

    /**
     * @var int
     * How many times we've tried to reconnect, as a way to introduce reconnection throttling.
     * This needs to be reset to zero at some point, beyond RPL_WELCOME, after the connection has been established for a significant amount of time.
     * That is, we're not just connecting, and being disconnected (kicked, banned, whatever) shortly after..
     */
    protected $reconnectAttempt = 0;

    protected $currentServerIpAddr;
    protected $waitingForHostResolve;
    protected $resolveStarted = 0;

    protected $serverMotd = '';
    protected $nick; // the current nickname
    protected $channels = [];
    protected $modules = []; // sub-modules, this system is currently disabled

    public function __construct(\Hubbub\Net\Client $net, \Hubbub\Logger $logger, \Hubbub\MessageBus $bus, \Hubbub\Configuration $conf, $name) {
        $this->net = $net;
        $this->logger = $logger;
        $this->bus = $bus;
        $this->conf = $conf;

        $this->bus->publish([

        ]);

        // Set the network's protocol to ths IRC object; the IRC object will receive event notifications via the network handler
        $this->net->setProtocol($this);

        // Setup our mbus subscription
        $this->bus->subscribe([$this, 'handleBusMessage']);

        $this->serverList = $this->conf->get($this->protocol . '.' . $name . '.serverList');
        $this->tryNext();
    }

    public function handleBusMessage($msg) {
        if(isset($msg['action']) && $msg['action'] == 'resolve-complete' && $this->state == 'attempt-resolve' && $this->currentServer == $msg['host']) {
            if(!empty($msg['result'])) {
                $this->logger->info("Resolved to: " . $msg['result']);
                $this->state = 'pre-auth';
                $this->currentServerIpAddr = $msg['result'];
                $this->connectNext();
            } else {
                $this->logger->notice("DNS lookup failed!");
            }
        }
    }

    protected function tryNext() {
        if(count($this->serverList) > 0) {
            $this->state = 'attempt-resolve';
            $this->resolveStarted = time();
            $nextServer = $this->serverList[($this->currentServerIdx++ % count($this->serverList))];

            if(strpos($nextServer, ':') !== null) {
                list($host, $port) = explode(':', $nextServer);
                $this->currentServer = $host;
                $this->currentPort = $port;
            } else {
                $this->currentServer = $nextServer;
                $this->currentPort = 6667;
            }

            $this->logger->info("Attempting to resolve hostname (AIHF): $nextServer");
            $this->bus->publish([
                'protocol' => 'dns',
                'action'   => 'resolve',
                'host'     => $this->currentServer
            ]);
        }
    }

    protected function connectNext() {
        $this->logger->info("Connecting to " . $this->currentServer . ':' . $this->currentPort . ' (' . $this->currentServerIpAddr . ')');
        $this->net->connect("tcp://" . $this->currentServerIpAddr . ':' . $this->currentPort);
    }

    /* --- Properly set states on connect and disconnect --- */
    public function on_connect($socket = null) {
        $this->sendUser($this->cfg['username'], $this->cfg['realname']);
        $this->sendNick($this->cfg['nickname']);
        $this->nick = $this->cfg['nickname'];
        $this->state = 'gave-auth';
    }

    public function send($data) {
        file_put_contents("log/raw-protocol.log", " > $data\n", FILE_APPEND);
        $this->logger->debug("RAW > $data");

        return $this->net->send("$data\n");
    }


    public function on_disconnect() {
        $this->state = 'disconnected';
        $this->nextAction = 'connect';

        $connectionDelay = (pow(2, $this->reconnectAttempt) * 30);

        if($connectionDelay > (3600 * 2)) {
            $connectionDelay = (3600 * 2);
        }

        $this->nextActionTime = time() + $connectionDelay;

        $this->reconnectAttempt++;
        $this->logger->info("IRC Client Disconnected.  Trying again in $connectionDelay seconds. (Attempt " . $this->reconnectAttempt . ")");

    }

    public function on_send($data) {

    }

    public function iterate() {
        if($this->nextActionTime <= time()) {
            if($this->nextAction == 'connect') {
                echo "next action...\n";
                $this->tryNext();
            } elseif($this->nextAction !== null) {
                $this->logger->warning("nextAction not handled");
            }
            $this->nextAction = null;
        }

        if($this->state == 'attempt-resolve' && ($this->resolveStarted + 60) > time()) {

        }

        //$this->logger->debug("IRC\\Client State: " . $this->state);
        $this->net->iterate();
    }

    public function on_recv($data) {
        $this->recvBuffer .= $data;
        $pos = strrpos($this->recvBuffer, "\r\n");
        // If the recvBuffer contains no fragmented messages
        if($pos !== false) {
            if(substr($this->recvBuffer, -2) == "\r\n") {
                $completeLines = substr($this->recvBuffer, 0, -2);
                $this->recvBuffer = '';
            } else {
                // else, there is a partially received message at the end.  so pull out the complete line(s) and tack the end fragment onto the buffer
                $completeLines = substr($this->recvBuffer, 0, $pos);
                $this->recvBuffer = substr($this->recvBuffer, $pos + 2);
            }

            $lines = explode("\r\n", $completeLines);
            foreach($lines as $line) {
                $this->logger->debug("RAW < $line");
                file_put_contents("log/raw-protocol.log", " < $line\n", FILE_APPEND);
                $this->on_recv_irc($line);
            }
        }
    }

    public function on_recv_irc($rawData) {
        /** @var \StdClass $data */
        $data = $this->parseIrcCommand($rawData);

        ob_start();
        var_dump($data);
        $contents = ob_get_contents();
        ob_end_clean();
        file_put_contents('log/parsed.log', $contents . "\n\n", FILE_APPEND);


        if($data->cmd == 'ping') {
            $this->sendPong($data->args[0]);
        } else {
            if(method_exists($this, 'on_' . $data->cmd)) {
                // Ideally this wouldn't need the additional logic
                $callbackResult = $this->{'on_' . $data->cmd}($data);
                if($callbackResult !== null) {
                    $data = $callbackResult;
                }
            }

            // Disabled code for sub-modules or per-protocol modules
            foreach($this->modules as $m) {
                $method_name = 'on_' . $data->cmd;
                $this->logger->debug("Trying method $method_name for class '" . get_class($m) . "'");
                if(method_exists($m, $method_name)) {
                    $m->$method_name($this, $data);
                } elseif(isset($cmd['cmd_numeric'])) {
                    $method_name = 'on_numeric_' . $cmd['cmd_numeric'];
                    $this->logger->debug("Trying method $method_name for class '" . get_class($m) . "'");
                    if(method_exists($m, $method_name)) {
                        $m->$method_name($this, $data);
                    }
                }
            }

            // Send this data across the bus for other modules to handle
            $this->bus->publish([
                'protocol' => $this->protocol,
                //'network'  => $this->network,
                //'event'    => 'msg',
                //'from'     => $data->sender,
                //'data'     => $data->data,
                'raw'      => $data->raw,
            ]);
        }
    }

    protected function bPublish($extra) {
        $extra['protocol'] = $this->protocol;
        $extra['network'] = 'freenode';
        $this->bus->publish($extra);
    }

    protected function on_err_nicknameinuse(StdClass $cmd) {
        $nick = $this->cfg['nickname'] . '-' . mt_rand(1111, 9999);
        $this->nick = $nick;
        $this->sendNick($nick);
    }

    protected function on_rpl_welcome(StdClass $cmd) {
        $this->sendJoin("#hubbub");
    }

    protected function on_rpl_motd(StdClass $line) {
        $this->serverMotd .= $line->motdLine;
    }

    protected function on_join(StdClass $cmd) {
        if($this->nick == $cmd->hostmask->nick) {
            $this->logger->debug("You have joined some channel(s)");
            foreach($cmd->join['channels'] as $channel) {
                $this->logger->debug("Adding $channel to joined chanenl list");
                $this->channels[$channel] = [
                    'joinedSince' => time(),
                    'names'       => [],
                    'topic'       => null,
                ];

                $this->bPublish([
                    'action'  => 'join',
                    'who'     => null,
                    'channel' => $channel,
                ]);
            }

        } else {
            $this->logger->debug("your nick: {$this->nick} and cmd->hostmask->nick: {$cmd->hostmask->nick}");
        }
    }

    protected function on_rpl_topic(StdClass $line) {
        $channel = $line->rpl_topic['channel'];
        $topic = $line->rpl_topic['topic'];
        if(isset($this->channels[$channel])) {
            $this->logger->debug("Set $channel topic to $topic");
            $this->channels[$channel]['topic'] = $topic;
        } else {
            $this->logger->alert("Got RPL_TOPIC for channel $channel that I haven't JOIN'd yet");
        }
    }

    protected function on_rpl_namreply(StdClass $line) {
        $channel = $line->rpl_namreply['channel'];
        if(isset($this->channels[$channel])) {
            foreach($line->rpl_namreply['names'] as $name) {
                $this->logger->debug("Added '$name' to $channel name list");
                $this->channels[$channel]['names'][] = $name;
            }
        } else {
            $this->logger->alert("Got RPL_NAMREPLY for channel $channel that I haven't JOIN'd yet");
        }

    }

    /*
     * Informational Replies
     */

    protected function on_rpl_yourhost(StdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_created(StdClass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_myinfo(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_isupport(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserclient(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserop(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserunknown(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserchannels(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_luserme(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_localusers(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }

    protected function on_rpl_globalusers(Stdclass $cmd) {
        $this->serverInfo = array_merge($this->serverInfo, [$cmd->cmd => $cmd->{$cmd->cmd}]);
    }
}