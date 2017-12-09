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
use App\Models\Order;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

class ParticipantPageController extends Controller
{ 
  private function getPlayersInfo() {
    $role = $this->container->sentinel->findRoleBySlug('participant');
    $users = $role->users()->orderBy('displayname', 'ASC')->get();
    $user_list = [];
      
    foreach ($users as $user) {        
      $user_list[] = self::getPlayerInfo($user->id);
    }
    
    return $user_list;
  }
  
  private function getSlugInfo($slug) {
    /*-------------------------
    + Populate values for specific slugs 
    -------------------------*/
    switch ($slug) {
      case "players":
         return self::getPlayersInfo();
        break;
    }
    
    return [];
  }
  
  public function index($request, $response, $arguments) // Dashboard
  {
    $participant = self::getCurrentUser();
    
    if(!isset($participant["attributes"]["onboarding_complete"])) {
      return $response->withRedirect($this->router->pathFor('participant.onboarding', ['hash' => $participant["user"]->hash]));
    }
    
    //Dashboard sections
    $this->container->view->getEnvironment()->addGlobal('dashboard', [
      'sections' => [
        /*
          'profile' => [
            'title' => 'My Profile',
            'target' => $this->router->pathFor('participant.page', ['page' => 'profile']),
          ],
        */
          'characters' => [
            'title' => 'Characters',
            'target' => $this->router->pathFor('participant.character.list'),
            'pages' => [
              'my' => [
                'title' => 'My Character',
                'target' => $this->router->pathFor('participant.character.my'),
                'info' => 'My character page',
              ],              
              'characters' => [
                'title' => 'Character List',
                'target' => $this->router->pathFor('participant.character.list'),
                'info' => 'Get the characters in list form',
              ],
              'gallery' => [
                'title' => 'Character Gallery',
                'target' => $this->router->pathFor('participant.character.gallery'),
                'info' => 'Character profile images',
              ],
            ]
          ],
          'players' => [
            'title' => 'Players',
            'target' => $this->router->pathFor('participant.player.list'),
            'pages' => [
              'players' => [
                'title' => 'Player List',
                'target' => $this->router->pathFor('participant.player.list'),
                'info' => 'Participant list',
              ],
              'gallery' => [
                'title' => 'Player Gallery',
                'target' => $this->router->pathFor('participant.player.gallery'),
                'info' => 'Participant profile image gallery',
              ],
            ]
          ],
          'relationships' => [
            'title' => 'Relationships',
            'target' => $this->router->pathFor('participant.relation.list'),
            'pages' => [
              'my' => [
                'title' => 'My Relationships',
                'target' => $this->router->pathFor('participant.relation.my'),
                'info' => 'My characters relationships',
              ],
              'list' => [
                'title' => 'Relationships',
                'target' => $this->router->pathFor('participant.relation.list'),
                'info' => 'Public relationships',
              ],
             'pending' => [
                'title' => 'Pending Relationships',
                'target' => $this->router->pathFor('participant.relation.pending'),
                'info' => 'Your pending relationships and public relationship requests',
              ],              
            ]
          ],
          'plots' => [
            'title' => 'Plots',
            'target' => $this->router->pathFor('participant.plot.list'),
            'pages' => [
              'my' => [
                'title' => 'My Plots',
                'target' => $this->router->pathFor('participant.plot.my'),
                'info' => 'My characters plots, and plots that my team or groups are involved in',
              ],
              'list' => [
                'title' => 'Plots',
                'target' => $this->router->pathFor('participant.plot.list'),
                'info' => 'Public plots list',
              ],
            ]
          ],
          'groups' => [
            'title' => 'Groups',
            'target' => $this->router->pathFor('participant.group.list'),
            'pages' => [
              'my' => [
                'title' => 'My Groups',
                'target' => $this->router->pathFor('participant.group.my'),
                'info' => 'Groups that I\'m part of',
              ],
              'list' => [
                'title' => 'Groups',
                'target' => $this->router->pathFor('participant.group.list'),
                'info' => 'Public groups',
              ],
            ]
          ],
          'schedules' => [
            'title' => 'Schedules',
            'target' => '#', #$this->router->pathFor('participant.schedule.list'),
            'pages' => [
              'my' => [
                'title' => 'My Schedule (TODO)',
                'target' => '#', #$this->router->pathFor('participant.group.my'),
                'info' => 'My work schedule',
              ],
              'list' => [
                'title' => 'Schedules (TODO)',
                'target' => '#', #$this->router->pathFor('participant.group.list'),
                'info' => 'Schedule lists',
              ],
            ]
          ],
        ],
    ]);
    
    return $this->view->render($response, '/new/participant/dashboard.html', [
      'current' => $participant,
    ]);
  }

  public function page($request, $response, $arguments)
  {
    $participant = self::getCurrentUser();
    $slug = filter_var($arguments['page'], FILTER_SANITIZE_STRING);
    $post = Post::where('slug', $slug)->visibleTo(['participant', 'admin'])->published()->first();
    
    if(!$post) return $response->withRedirect($this->router->pathFor('participant.home'));
    
    //Slug info will be populated under the same key as the slug name
    $this->container->view->getEnvironment()->addGlobal(
      $slug, self::getSlugInfo($slug)
    );
    
    return $this->view->render($response, '/new/participant/page.html', [
      'post' => $post,
      'current' => $participant
    ]);
  }  
}