<?php

namespace App\Middleware;

class WriterMiddleware extends Middleware
{
  public function __invoke($request, $response, $next)
  {
    if ($this->container->sentinel->getUser()) {
      $isAdmin = $this->container->sentinel->getUser()->inRole('admin');
      $isWriter= $this->container->sentinel->getUser()->inRole('writer') || $isAdmin;
    }

    if (!$isWriter) {
      $this->container->flash->addMessage('error', 'You have no access to view this writer page.');
      $_SESSION['redirect_uri'] = $request->getUri()->getPath();
      return $response->withRedirect($this->container->router->pathFor('user.login'));
    }

    $response = $next($request, $response);
    return $response;
  }
}