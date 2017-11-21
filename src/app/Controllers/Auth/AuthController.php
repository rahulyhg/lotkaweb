<?php
namespace App\Controllers\Auth;

use App\Models\User;
use App\Models\Post;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Mail\Sender;
use App\Mail\Templater;
  
class AuthController extends Controller
{
  private function sendEmail($recipient, $subject, $body, $vars) {
    $mail = new Sender($this->container->get('settings'));
    return $mail->send($recipient, $subject, $body, $vars);
  }
  
  public function getLogin($request, $response)
  {
    return $this->view->render($response, 'new/user/login.html');
  }

  public function postLogin($request, $response)
  {
    $credentials = [
      'username' => $request->getParam('username'),
      'password' => $request->getParam('password')
    ];

    $attempt = $this->container->sentinel->authenticate($credentials);

    if (!$attempt) {
      $this->flash->addMessage('error', "There was an error with your login. Please check your credentials.");
      return $response->withRedirect($this->router->pathFor('user.login'));
    } else {
      $this->container->sentinel->login($attempt);
      return $response->withRedirect($this->router->pathFor('home'));
    }
  }

  public function logout($request, $response)
  {
    $this->container->sentinel->logout();
    return $response->withRedirect($this->router->pathFor('home'));
  }

  public function getRegister($request, $response)
  {
    return $this->view->render($response, 'new/user/register.html');
  }

  public function postRegister($request, $response)
  {
    $credentials = [
      'username' => $request->getParam('username'),
      'displayname' => $request->getParam('displayname'),
      'email' => $request->getParam('email'),
      'password' => $request->getParam('password')
    ];

    $validation = $this->validator->validate($request, [
      'username' => v::noWhitespace()->notEmpty()->userAvailable(),
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
      'password' => v::noWhitespace()->notEmpty(),
    ]);

    if ($validation->failed()) {
      return $response->withRedirect($this->router->pathFor('user.register'));
    }

    $user = $this->container->sentinel->registerAndActivate($credentials);
    $role = $this->container->sentinel->findRoleByName('User');
    $role->users()->attach($user);

    $this->flash->addMessage('success', 'You have been successfully registered. Login now.');
    return $response->withRedirect($this->router->pathFor('user.login'));
  }

  public function postReminder($request, $response)
  {
    $credentials = [
      'email' => $request->getParam('email'),
    ];
    
    $validation = $this->validator->validate($request, [
      'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
    ]);    
    
    if ($validation->failed()) { //we have a user with this email
      $template = Post::where('slug', 'password-reminder')->first();
      
      if( self::sendEmail(
        $credentials['email'], // Recipiant
        $template->title,      // Subject Line
        $template->content,    // E-mail Body
        [                      // Values ([{###}] where ### is the KEY)
          "CODE" => 'KODEKODEKODEKODEO'
        ]
      ) ) {
        $this->flash->addMessage('success', "Check you email for login reset information.");
      } else {
        $this->flash->addMessage('error', "There's a problem with the email function. (Sorry)");
      };
      
    }
    
    return $response->withRedirect($this->router->pathFor('user.login'));
  }
  
  public function getResetPassword($request, $response)
  {
    return $this->view->render($response, 'new/user/reset.html', [
      
    ]);
  }
  
  public function resetPassword($request, $response)
  {
    
  }  
}
