<?php 

return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => getenv('DISPLAY_ERROR_DETAILS'),
        'db' => [
            'driver'    => 'mysql',
            'host'      => getenv('DB_HOSTNAME'),
            'port'      => getenv('DB_PORT'),
            'database'  => getenv('DB_DATABASE'),
            'username'  => getenv('DB_USERNAME'),
            'password'  => getenv('DB_PASSWORD'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
        ],
    ],
];