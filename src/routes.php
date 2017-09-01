<?php
// Routes
$stripe_settings = $container->get('settings')['stripe'];
\Stripe\Stripe::setApiKey($stripe_settings['SECRET_KEY']);
\Stripe\Stripe::setApiVersion($stripe_settings['API_VERSION']);

/*
| OPEN PAGE ROUTES
*/

$app->get('/[pay/{sku}]', '\App\Pages\OpenPage:index');
$app->post('/charge', 'App\Pages\OpenPage:charge');

/*
| API ROUTES
*/


$app->group('/api/v1', function () {
  $this->get('/test', '\App\API\Names:test');
  
  $this->get('/names', function ($request, $response, $args) {
    $names = \App\API\Names::get();
    
    return $this
      ->json_provider
      ->withOk(
        $response, 
        ['surnames' => $names], 
        "Unreserved names as of: ". date("Y-m-d H:i:s")
    );
  });

  $this->get('/reserve/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
    $res = \App\API\Names::reserve($name);

    if ($res) {
      return $this->json_provider->withOk(
          $response, 
          ['surnames' => $name], 
          "Surname reserved on ". date("Y-m-d H:i:s")
        );
    } else {
      return $this->json_provider->withError(
          $response, 
          "Surname could not be reserved. It either does not exist or is not available.", 
          400, 
          ['surname' => $name]
        );
    }

  });

  $this->get('/release/{name}', function ($request, $response, $args) {
    $name = filter_var($args['name'], FILTER_SANITIZE_STRING);
    $res = \App\API\Names::release($name);

    if ($res) {
      return $this->json_provider->withOk(
        $response, 
        ['surnames' => $name], 
        "Surname released on ". date("Y-m-d H:i:s")
      );
    } else {
      return $this->json_provider->withError(
        $response, 
        "Surname could not be released. It either does not exist or is reserved via order.", 
        400, 
        ['surname' => $name]
      );
    }
  });
  
  $this->get('/external/texttalk', function ($request, $response, $args) {
    $texttalk_settings = $this->get('settings')['external_stores']['texttalk'];
    $choices = \App\API\Names::updateFromTextTalk($texttalk_settings);
    
    return $this->json_provider->withOk(
      $response, 
      ["surnames" => $choices], 
      "Names reserved by external provider TextTalk."
    );
  });
  
});