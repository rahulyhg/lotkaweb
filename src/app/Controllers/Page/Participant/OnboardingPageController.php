<?php

namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Attribute;
use App\Models\User;
use App\Models\Task;

use App\Controllers\Controller;
use App\Controllers\Admin\UserActionController;

use Slim\Views\Twig as View;

class OnboardingPageController extends Controller
{
  private function onboardingAttributes() {
    return [

    ];
  }
  
  private function getUserData($request) {
    $user_data_set = [ 'username', 'email', 'first_name', 'last_name' ];
    $user_data = [];
    foreach ($user_data_set as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $user_data[$attr] = $request->getParam($attr);
      }
    }
    
    if($request->getParam('first_name') || $request->getParam('last_name')) {
      $user_data['displayname'] = "{$request->getParam('first_name')} {$request->getParam('last_name')}";
    }
    
    if (($request->getParam('password') && $request->getParam('password_repeat')) && 
        ($request->getParam('password') == $request->getParam('password_repeat'))) {
      $user_data['password'] = $request->getParam('password');
    }
    
    return $user_data;
  }
  
  private function getUserAttributeIds($request, $stage) {
    $user_attribute_set = [ 
      'onboarding_complete', 'gender', 'id_number_swe', 'birth_date', 
      'membership_fee', 'membership_date', 'care_of', 'street_address_1', 
      'street_address_2', 'postal_code', 'state', 'city', 'country', 'phone', 
      'emergency_contact', 'emergency_phone',
    ];
        
    $stages_total = Post::where('slug', 'like', "stage-%")->get()->count();
    $attributes = [ 
      'keys' => ['onboarding_stage', 'onboarding_complete'], 
      'values' => [$stage, $stages_total == $stage - 1] 
    ];
    foreach ($user_attribute_set as $attr) {
      if ( strlen($request->getParam($attr)) ) {
        $attributes['keys'][] = $attr;
        $attributes['values'][] = $request->getParam($attr);
      }
    }

    $attribute_ids = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    
    return $attribute_ids;
  }
  
  private function getCharacterData($request) {

  }
  
  private function getCurrentUser($hash) {
    $participant = User::where('hash', $hash)->first();
    
    return $participant ? [
      "user" => $participant,
      "attributes" => self::mapAttributes( $participant->attr ),
    ] : false;
  }
  
  public function onboarding($request, $response, $arguments)
  {    
    $user_hash = filter_var($arguments['hash'], FILTER_SANITIZE_STRING);
    $participant = self::getCurrentUser($user_hash);
    
    if(!$participant) {
      $this->flash->addMessage(
        'error', 
        "Sorry, this participant could not be found for onboarding or the link has allready been used."
      );
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $stage = isset($arguments['stage']) ? $arguments['stage'] : 1;
    $user_stage = isset($participant["attributes"]["onboarding_stage"]) ? $participant["attributes"]["onboarding_stage"] : 1;    
    $stage_nr = $user_stage == $stage ? $user_stage : min($user_stage, $stage);
    
    $stages_total = Post::where('slug', 'like', "stage-%")->get()->count();
    
    $stage = Post::where('slug', "stage-$stage_nr")->first();

    $this->container->view->getEnvironment()->addGlobal('data', [
      'genders' => ['Nonbinary','Female','Male','Other'],
    ]);
    
    return $this->view->render($response, '/new/participant/onboarding/stages.html', [
      'stage_nr' => $stage_nr,
      'stages_total' => $stages_total,
      'stage' => $stage,
      'participant' => $participant,
    ]);
  }
  
  
  
  public function updateUser($user_data, $user_attributes, $user) {
    $user->attr()->syncWithoutDetaching($user_attributes);
    return $user->update($user_data);
  }  

  public function save($request, $response, $arguments)
  {
    $user_hash = filter_var($arguments['hash'], FILTER_SANITIZE_STRING);
    $participant = self::getCurrentUser($user_hash);
    
    if(!$participant) {
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $next_stage_nr = isset($participant["attributes"]["onboarding_stage"]) ?
        $participant["attributes"]["onboarding_stage"] + 1 : 2;
    
    $user_data = self::getUserData($request);
    $user_attributes = self::getUserAttributeIds($request, $next_stage_nr);
    
    if(!self::updateUser($user_data, $user_attributes, $participant["user"])) {
      $this->flash->addMessage('error', "Something seems to be out of wack, have a look and make sure that you have filled out all the required fields.");
      $next_stage_nr = $next_stage_nr > 1 ? $next_stage_nr - 1 : 1;
    }
    
    return $response->withRedirect($this->router->pathFor(
      'participant.onboarding', 
      ['hash' => $user_hash, 'stage' => $next_stage_nr]
    ));
  }  
}