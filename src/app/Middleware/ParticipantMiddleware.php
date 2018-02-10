<?php

namespace App\Middleware;

class ParticipantMiddleware extends Middleware
{
  public function __invoke($request, $response, $next)
  {
    if ($this->container->sentinel->getUser()) {
      $isAdmin = $this->container->sentinel->getUser()->inRole('admin');
      $isParticipant = $this->container->sentinel->getUser()->inRole('participant') || $isAdmin;
    }

    if (!$isParticipant) {
      $this->container->flash->addMessage('error', 'You have no access to view this page.');
      $_SESSION['redirect_uri'] = $request->getUri()->getPath();     
      return $response->withRedirect($this->container->router->pathFor('user.login'));
    }

    $response = $next($request, $response);
    return $response;
  }
}
