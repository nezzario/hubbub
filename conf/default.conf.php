<?php
/*
 * Example static config file: Will be switched out for something more dynamic eventually.
 */

return [
    // These are the objects to auto-create
    'hubbub'             => [
        'dns'      => '\Hubbub\DNS\DigResolver',
        'bnc'      => '\Hubbub\IRC\Bnc',
        //'example'  => '\Hubbub\ExampleProtocol\Client',
        'freenode' => '\Hubbub\IRC\Client',
    ],

    // This actually works
    'logger'             => [
        'logToFile'    => 'hubbub.log',
        'contextDumps' => true, // TODO Does not care about this setting
    ],

    // This actually works
    'throttler'          => [
        'frequency' => 100000,
    ],

    // Our local "test" configuration; this should be removed once we are beyond a certain point of usability
    'irc' => [
        'freenode' => [
            // Peg the server list to a dynamic server list.
            'serverListPegged' => 'freenode',
            // The list of servers, or if it is pegged, the cached/last updated copy of the server list
            'serverList'       => [
                'irc.freenode.net:6667',
            ],
            'modules'          => [

                /*
                 * What is the best way to de-couple, or possibly rather properly couple, things such as channel management (keep topic, keep modules,
                 * services integration, etc) along with more simpler aspects such as auto-join, etc?
                 *
                 * Or more confusingly, say we want to log only #a and #b but not #c, -- this creates friction with the Logger.
                 */

                'channels' => [
                    '#hubbub'
                ]
            ]

        ],

        'bnc' => [
            'listen'    => '0.0.0.0:1337',
            'password'  => '1234',
            'motd_file' => 'LICENSE.txt',
        ]
    ],


    // This is meant to be the default configuration for this class, but it doesn't work yet
    '\Hubbub\IRC\Client' => [
        'nickname'  => 'HubTest-' . mt_rand(0, 999),
        'username'  => 'Hubbub',
        'realname'  => 'Hubbub',

        // Default part & quit messages if none are specified otherwise
        'partmsg'   => '',
        'quitmsg'   => 'I use Hubbub, the php irc client: http://github.com/nezzario/hubbub',

        // How long to wait between reconnects, or 0 to never reconnect if disconnected
        'reconnect' => 30,
        'modules'   => [

            /*
             * This is interesting as we need to load these modules for /each/ irc object
             * In addition, we need to be able to not "copy" these configurations, so e.x.
             * you should be able to update the keepNick.retry variable for the global
             * setting, and if I had keepNick.retry = 0 (off) for network A, then it would
             * not affect network A's setting but only if it was inherited
             */

            'keepNick' => [
                // How long to wait between nick regain retries
                'retry' => 300,
            ],
            /*
             * These are more complicated as they seem because they require interaction with a BNC, and as such their
             * configuration is stuck somewhere in-between the IRC and BNC realm.
             */

            'ctcp' => [
                'mode'    => 'static', // Needs a static mode, pass-thru mode, block mode, pass-thru-when-active (ie pass thru if bnc is active)
                'version' => 'I use Hubbub, the php irc client: http://github.com/nezzario/hubbub'
            ],
            'dcc'  => [
                'file' => [
                    'file' => 'auto-accept|prompt|ignore|pass-thru',
                    'chat' => 'auto-accept|prompt|ignore|pass-thru',
                ]
            ]


        ]
    ],
];