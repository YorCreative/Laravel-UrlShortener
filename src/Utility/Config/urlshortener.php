<?php

return [
    'branding' => [
        'views' => [
            'protected' => [
                'images' => [
                    'image-1' => 'https://cdn-icons-png.flaticon.com/512/180/180954.png',
                ],
                'content' => [
                    'message' => 'Password Protected',
                    'title' => 'Whoa! A Password Protected Route!',
                ],
            ],
        ],
        'prefix' => 'something/pretty/cool',
        'host' => getenv('APP_URL'),
        'identifier' => [
            'length' => 6,
        ],
    ],
    'protection' => [
        'pwd_req' => [
            'min' => 6,
            'max' => 32,
        ],
    ],
    'redirect' => [
        'code' => 307,
        /**
         * 301: Moved Permanently
         * 302: Found / Moved Temporarily
         * 303: See Other
         * 304: Not Modified
         * 307: Temporary Redirect
         * 308: Permanent Redirect
         */
        'headers' => [
            'Referer' => getenv('APP_URL', 'localhost:1337'),
        ],
    ],
];
