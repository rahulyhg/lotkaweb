<?php
// Application middleware

$app->add(new \App\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \App\Middleware\CsrfViewMiddleware($container));
