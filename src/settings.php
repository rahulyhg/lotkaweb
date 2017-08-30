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

        'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'lotka-volterra',
            'username' => 'root',
            'password' => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
      
        'stripe' => [
            'SECRET_KEY' => 'sk_test_guDNkknGzHaSWG1LN2QGlJXn',
            'PUBLIC_KEY' => 'pk_test_zCWWc29QQlnsSfZtMwqx2qBp',
            'API_VERSION' => '2017-08-15',
        ],
      
        'external_stores' => [
          'texttalk' => [
            'admin' => [
              'user' => 'info@beratta.org',
              'password' => '<OUR_PASSWORD_HERE>',
            ],
            'shop_id' = > 70834,
          ],
        ],
    ],
    'json_provider' => function() { return new SJsonResponseProvider(); }
];
