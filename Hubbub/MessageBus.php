<?php

/*
 * This file is a part of Hubbub, available at:
 * http://github.com/abcarroll/hubbub
 *
 * Copyright (c) 2015, A.B. Carroll <ben@hl9.net>
 * Hubbub is distributed under a BSD-like license.
 *
 * For full license terms, please view the LICENSE.txt file that was
 * distributed with this source code, or available at the URL above.
 */

namespace Hubbub;

/**
 * Class MessageBus
 *
 * @package Hubbub
 */
class MessageBus implements Iterable { // @todo not actually an iterable module ?
    protected $subscriptions;

    /**
     * @param Configuration|null $conf   The configuration object
     * @param Logger|null        $logger The logger object
     */
    public function __construct(\Hubbub\Configuration $conf = null, \Hubbub\Logger $logger = null) {
        $this->conf = $conf;
        $this->logger = $logger;
    }

    /**
     * @param callable          $callback
     * @param string|array|null $filter
     *
     * @return int The subscription key that can be used later to unsubscribe
     */
    public function subscribe($callback, $filter = null) {
        $this->subscriptions[] = [
            'filter'   => $filter,
            'callback' => $callback,
        ];

        return key(end($this->subscriptions));
    }


    public function unsubscribe($id) {
        if(isset($this->subscriptions[$id])) {

        }
    }

    public function publish($message) {

    }

    public function setConf(\Hubbub\Configuration $conf) {
        // @todo
    }

    public function setLogger(\Hubbub\Logger $logger) {
        // @todo
    }

    public function iterate() {
        // does nothing @todo
    }
}
