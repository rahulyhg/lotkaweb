<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Models\Post;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

use App\Mail\Sender;
use App\Mail\Templater;

class BulkmailActionController extends Controller
{
  private function sendEmail($recipient, $subject, $body, $vars) {
    $mail = new Sender($this->container->get('settings'));
    return $mail->send($recipient, $subject, $body, $vars);
  }
  
  private function userAttributes() {
    return [
      'allergies', 
      'aspects', 
      'birth_date', 
      'care_of', 
      'char_dir_avo', 
      'char_gender', 
      'char_ind_col', 
      'char_iso_int', 
      'char_log_int', 
      'char_mal_con', 
      'char_mil_dem', 
      'char_nos_pro', 
      'char_phy_non', 
      'city', 
      'country', 
      'emergency_contact', 
      'emergency_phone', 
      'feedback',
      'gender', 
      'group',
      'id_number_swe', 
      'medical_conditions',
      'membership_date', 
      'membership_fee', 
      'onboarding_complete', 
      'onboarding_stage',
      'onefifty_plus', 
      'password_set',
      'phone', 
      'player_connections', 
      'player_unwanted', 
      'portrait',       
      'postal_code', 
      'pref_bus',
      'pref_conflict_ideological', 
      'pref_conflict_intrapersonal', 
      'pref_counselling', 
      'pref_everyday', 
      'pref_friendships', 
      'pref_interrupted_sleep', 
      'pref_late_arival', 
      'pref_ooc_leader',
      'pref_ooc_medic', 
      'pref_ooc_radio',
      'pref_player_def_1', 
      'pref_player_def_2', 
      'pref_player_def_3', 
      'pref_responsibilities', 
      'pref_romance', 
      'pref_secrets', 
      'pref_work', 
      'size', 
      'state', 
      'street_address_1', 
      'street_address_2',
      'terms_accepted',
      'torso_circumference', 
    ];
  }
  
  private function userValues() {
    return [ 
      'username',
      'displayname',
      'email',
      'password',
      'first_name',
      'last_name',
      'org_notes',
      'permissions',
      'character_id',
      'hash',
    ];
  }
  
  public function compose($request, $response, $arguments)
  {
    
    $templates = Post::where('slug', 'like', '%email%')->get();
    $users = User::get();
    
    return $this->view->render($response, 'admin/bulkmail/compose.html', [
      'templates' => $templates,
      'users' => $users,
      'userAttributes' => self::userAttributes(),
      'userValues' => self::userValues(),
      'default_subject' => strtoupper($this->container->get('settings')['event']['name'])
    ]);
  }
  
  public function send($request, $response, $arguments)
  {
    $params = $request->getParsedBody();    
    $vars = [];
    $recipiants = explode(",", $params['recipiants']);
    $default_subject = "[" . strtoupper($this->container->get('settings')['event']['name']) . "] ";
    $sent_log = [];

    foreach($recipiants as $recipiant) {
      
      $user = User::where('email', $recipiant)->first();
      $user_attr = $user ? self::mapAttributes($user->attr) : [];
      
      foreach ($params['attrKey'] as $i => $attr_key) {
        $val = $params['attrVal'][$i];
        $key = substr($val, 5);
        $vars[$attr_key] = $val;
        
        if($user) {
          if(strpos($val, 'attr.') === 0) {
            $vars[$attr_key] = isset($user_attr[$key]) ? $user_attr[$key] : '';
          } else if (strpos($val, 'user.') === 0)  {
            $vars[$attr_key] = isset($user[$key]) ? $user[$key] : '';
          }
        }
      }
      
      $sent_log[$recipiant] = self::sendEmail(
        $recipiant,                              // Recipiant
        $default_subject . $params['subject'],   // Subject Line
        $params['template'],                     // E-mail Body
        $vars                                    // Values ([{###}] where ### is the KEY)
      );
    }

    if( in_array(false, $sent_log, true)  ) {
      $fails = array_filter($sent_log, function ($val) { return !$val; });
      $this->flash->addMessage('error', "Something went wrong, there email to could not be sent. (Failed recipients: " . implode(", ", array_keys($fails)) . ")");
    } else {
      $this->flash->addMessage('success', "Email was successfully sent!");
    };
    
    return $response->withRedirect($this->router->pathFor('admin.bulkmail')); 
  }
}