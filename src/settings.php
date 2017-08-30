<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => false, #__DIR__ . '/../cache/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'stripe' => [
            'SECRET_KEY' => 'sk_test_guDNkknGzHaSWG1LN2QGlJXn',
            'PUBLIC_KEY' => 'pk_test_zCWWc29QQlnsSfZtMwqx2qBp',
            'API_VERSION' => '2017-08-15'
        ],
    ],
    'json_provider' => function() { return new SJsonResponseProvider(); }
  //hello worl
];
