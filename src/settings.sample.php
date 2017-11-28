<?php
return [
  'settings' => [
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header
    'default_salt' => '<OUR_DEFALT_PASSWORD_SALT>', //Used to create default passwords to generated users
    'user_images' => __DIR__ . '/../public/assets/portraits/',
    
    // Renderer settings
    'renderer' => [
      'template_path' => __DIR__ . '/../templates/',
      'cache_path' => __DIR__ . '/../cache/',
      'auto_reload' => true,
      'debug' => true
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
      'username' => '<OUR_DB_USERNAME_HERE>',
      'password' => '<OUR_DB_PASSWORD_HERE>',
      'charset'   => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix'    => '',
    ],

    'mail' => [
      'from' => [
        'email' => <FROM_MAIL>,
        'name' => <FROM_NAME>
      ],

      'smtp' => [
        'server' =>	  'smtp.gmail.com', 
        'email' => 	  <SENDER_EMAIL>, 
        'password' =>	<SENDER_EMAIL_PASWORD>, 
        'port' =>    	587,
      ],
    ],
    
    'stripe' => [
      'SECRET_KEY' => '<OUR_SECRET_STRIPE_KEY_HERE>',
      'PUBLIC_KEY' => '<OUR_PUBLIC_STRIPE_KEY_HERE>',
      'API_VERSION' => '2017-08-15',
    ],

    'external_stores' => [
      'texttalk' => [
        'admin' => [
          'user' => '<OUR_TEXTTALK_USER_HERE>',
          'password' => '<OUR_TEXTTALK_PASSWORD_HERE>',
        ],
        'shop_id' => '<OUR_TEXTTALK_SHIP_ID_HERE>',
      ],
    ],
    
    'event' => [
      'date' => '5 April 2018',
      'ticket' => [
        'target' => <OUR_TICKET_TARGET_AMOUNT>,
        'goal' => <OUR_TICKET_SALES_GOAL>,
      ],
      'devBlog' => [
        'active' => false,
        'uri' => 'https://lotka-volterra.se/dev/',        
      ]      
    ],    
  ],
  'json_provider' => function() { return new SJsonResponseProvider(); }
];
