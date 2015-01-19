<?php

/*
 * This file is a part of Hubbub, freely available at http://hubbub.sf.net
 *
 * Copyright (c) 2013, Armond B. Carroll <ben@hl9.net>
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code.
 */

class bnc extends net_stream_server {
    protected $hubbub;
    protected $clients;

    public function __construct($hubub) {
        $this->hubbub = $hubub;
        parent::__construct($hubub->config->bnc['listen']);;
    }

    function on_client_connect($socket) {
        $newClient = new bnc_client($this->hubbub, $this, $socket);
        $newClient->iterate(); // Iterate once after connection automatically
        $this->clients[(int) $socket] = $newClient;
    }

    function on_client_disconnect($socket) {
        unset($this->clients[(int) $socket]);
    }

    function on_client_recv($socket, $data) {
        /** @var bnc_client $client */
        $client = $this->clients[(int) $socket];
        $client->on_recv($data);
    }

    function on_client_send($socket, $data) {
        /* this may be a moot method anyway.  we have no way of actually controlling
           when data is sent. */
    }

    function on_iterate() {
        if(count($this->clients) > 0) {
            foreach ($this->clients as $c) {
                /** @var $c bnc_client */
                $c->iterate();
            }
        }
        $this->hubbub->logger->debug("BNC Server was iterated with " . count($this->clients) . " clients");
    }
}
