<?php

namespace App\Controllers\Page\Participant;

use App\Models\Post;
use App\Models\Character;
use App\Models\Plot;
use App\Models\Group;
use App\Models\Relation;
use App\Models\Attribute;
use App\Models\User;
use App\Models\Order;
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
  
  
  private function fetchAttributeIdsFromDB($attributes = [ 'keys' => [], 'values' => [] ]) {
    $attribute_ids = [];
    foreach ($attributes['keys'] as $i => $attr_key) {
      $attribute_ids[] = Attribute::firstOrCreate([
        'name' => $attr_key, 
        'value' => $attributes['values'][$i]
      ])->id;
    }
    return $attribute_ids;
  }
  
  private function getUserAttributeIds($request) {
    $user_attribute_set = [ 
      'gender', 'id_number_swe', 'birth_date', 
      'membership_fee', 'membership_date', 'care_of', 'street_address_1', 
      'street_address_2', 'postal_code', 'state', 'city', 'country', 'phone', 
      'emergency_contact', 'emergency_phone', 'allergies', 'medical_conditions',
      'player_connections', 'char_gender', 
      'char_iso_int', 'char_mil_dem', 'char_nos_pro', 'char_ind_col', 
      'char_log_int', 'char_dir_avo', 'char_phy_non', 'char_mal_con', 
      'late_arival', 'interrupted_sleep', 'radio', 'off_medic', 'occ_leader',
      'pref_counselling', 'pref_romance', 'pref_responsibilities', 
      'pref_conflict_ideological', 'pref_conflict_intrapersonal', 
      'pref_work', 'pref_everyday', 'pref_friendships', 'pref_secrets', 
      'pref_player_def_1', 'pref_player_def_2', 'pref_player_def_3', 
    ];

    $attributes = [ 'keys' => [], 'values' => [] ];
    foreach ($user_attribute_set as $attr) {
      $attribute_value = $request->getParam($attr);

      if ( is_array($attribute_value) ? count($attribute_value) : strlen($attribute_value) ) {
        if(is_array($attribute_value)) {
          foreach ($attribute_value as $i => $val) {
            $attributes['keys'][] = $attr;
            $attributes['values'][] = $attribute_value[$i]; 
          }
        } else {
          $attributes['keys'][] = $attr;
          $attributes['values'][] = $attribute_value;          
        }
          
      }
    }
    
    return self::fetchAttributeIdsFromDB( $attributes );
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

    if(!$participant || $participant["attributes"]["onboarding_complete"]) {
      $this->flash->addMessage(
        'error', 
        "Sorry, this participant could not be found for onboarding or the link has allready been used."
      );
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $user_order = Order::where('user_id', $participant["user"]->id)->first();
    
    if(!$user_order) {
      $this->flash->addMessage(
        'error', 
        "Sorry, there's no ticket connected to this user. Particians need a ticket to be onboarded."
      );
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $stage = isset($arguments['stage']) ? $arguments['stage'] : 1;
    $stage = filter_var($stage, FILTER_SANITIZE_NUMBER_INT);
    $user_stage = isset($participant["attributes"]["onboarding_stage"]) ? 
      $participant["attributes"]["onboarding_stage"] : 1;
    $stage_nr = $user_stage == $stage ? $user_stage : min($user_stage, $stage);

    $stages_total = Post::where('slug', 'like', "stage-%")->get()->count();
    $stage = Post::where('slug', "stage-$stage_nr")->first();

    $this->container->view->getEnvironment()->addGlobal('data', [
      'genders' => ['Nonbinary','Female','Male','Other'],
      'user_order' => $user_order,
    ]);
            
    return $this->view->render($response, '/new/participant/onboarding/stages.html', [
      'stage_nr' => $stage_nr,
      'stages_total' => $stages_total,
      'stage' => $stage,
      'participant' => $participant,
      'hash' => $user_hash,
    ]);
  }

  private function uploadFile($uploadedFiles, $user) {
    $directory = $this->container->get('settings')['user_images'];

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['portrait'];
    
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
      $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
      $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
      $filename = sprintf('%s.%0.8s', $basename, $extension);

      $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
      
      self::updateAttributes(['portrait' => $filename], $user);
    }
  }
  
  private function updateAttributes($attributes, $user) {
    foreach ($attributes as $name => $value) {
      $existing_attribute = $user->attr()->where('name', $name)->get();
      if(!$existing_attribute->isEmpty()) {
        $user->attr()->detach($existing_attribute); 
      }
      
      if(is_array($value)) {
        foreach ($value as $i => $val) {
          $user->attr()->attach(Attribute::firstOrCreate(['name' => $name, 'value' => $val]));
        }
      } else {
        $user->attr()->attach(Attribute::firstOrCreate(['name' => $name, 'value' => $value]));
      }
    }
  }
  
  private function updateUser($user_data, $user_attributes, $user, $stage, $stages_total) {
    $onboarding_attributes = [ 
      'onboarding_stage' => $stage + 1, 
      'onboarding_complete' => $stages_total == $stage
    ];
    
    $this->container->sentinel->update($user, $user_data);
    $user->attr()->syncWithoutDetaching($user_attributes);
    self::updateAttributes($onboarding_attributes, $user);
    
    return true;
  }  

  public function save($request, $response, $arguments)
  {
    $user_hash = filter_var($arguments['hash'], FILTER_SANITIZE_STRING);
    $participant = self::getCurrentUser($user_hash);
    
    if(!$participant) {
      return $response->withRedirect($this->router->pathFor('home'));
    }
    
    $stages_total = Post::where('slug', 'like', "stage-%")->get()->count();
    
    $stage = isset($arguments['stage']) ? $arguments['stage'] : 1;
    $stage = filter_var($stage, FILTER_SANITIZE_NUMBER_INT);
    $user_stage = isset($participant["attributes"]["onboarding_stage"]) ? $participant["attributes"]["onboarding_stage"] : 1;
    $stage_nr = $user_stage == $stage ? $user_stage : min($user_stage, $stage);
    
    $user_data = self::getUserData($request);
    $user_attributes = self::getUserAttributeIds($request, $stage_nr);

    $hasUpload = $request->getUploadedFiles();
    if( isset($hasUpload['portrait']) ) {
      self::uploadFile($hasUpload, $participant["user"]);
    }
    
    if(strlen($request->getParam('aspects'))) {
      $aspects_attributes = [
        'aspects' => $request->getParam('aspects'), 
      ];
      
      $groups = $request->getParam('group');
      if(is_array($groups)) {
        foreach ($groups as $i => $val) {
          $aspects_attributes['group'][] = $groups[$i];
        }
      }
      
      self::updateAttributes($aspects_attributes, $participant["user"]);
    }
 
    if(!self::updateUser($user_data, $user_attributes, $participant["user"], $stage_nr, $stages_total)) {
      $this->flash->addMessage('error', "Something seems to be out of wack, " . 
                               "have a look and make sure that you have filled out all the required fields.");
    }
    
    if($stage_nr == $stages_total) {
      self::updateAttributes(['onboarding_complete' => true], $participant["user"]);
      return $response->withRedirect($this->router->pathFor('participant.home'));
    }
    
    return $response->withRedirect($this->router->pathFor(
      'participant.onboarding', 
      ['hash' => $user_hash, 'stage' => $stage_nr + 1]
    ));
  }  
}