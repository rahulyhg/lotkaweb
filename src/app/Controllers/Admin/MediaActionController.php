<?php

namespace App\Controllers\Admin;

use App\Models\Media;
use App\Controllers\Controller;
use Slim\Views\Twig as View;

class MediaActionController extends Controller
{
  private function uploadFile($uploadedFiles, $media) {
    $directory = $this->container->get('settings')['media_directory'];

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['file'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {        
      $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
      $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
      $filename = sprintf('%s.%0.8s', $basename, $extension);

      $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
      $media->update(['filename' => $filename]);
    }
  }  
  
  public function index($request, $response)
  {
    $media_files = Media::select('*')->orderBy('name')->get();
    $settings = [
      'name' => 'Media',
      'title' => 'Media Management',
      'breadcrumb' => [
//          ['name' => '', 'path' => '']
      ],
      'columns' => [
        ['title' => 'Name', 'field_name' => 'name', /*'class' => ''*/],
        ['title' => 'Filename', 'field_name' => 'filename', /*'class' => ''*/],
      ],
      'actions' => [
        ['path' => 'admin.media.edit', 'class' => 'default', 'title' => 'Edit media', 'icon' => 'edit'],
      ],
    ];
    
    return $this->view->render($response, 'admin/partials/list.html', [
      'settings' => $settings,
      'list' => $media_files,
    ]);
  }
  
  public function add($request, $response)
  {
    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => [],
      'new' => true
    ]);
    
    $this->container->view->getEnvironment()->addGlobal('settings', [
      'name' => 'Edit media',
      'title' => 'Media Management',
      'breadcrumb' => [
        ['name' => 'Media', 'path' => 'admin.media.all']
      ],
    ]);
    
    return $this->view->render($response, 'admin/media/edit.html');    
  }
  
  public function postAdd($request, $response)
  {
    return self::postEdit($request, $response, ['uid' => null]);
  }
  
  public function edit($request, $response, $arguments)
  {
    $mediaData = Media::where('id', $arguments['uid'])->first();
    
    if(!$mediaData) {
      $this->flash->addMessage('error', "No media with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.media.all'));     
    }

    $this->container->view->getEnvironment()->addGlobal('current', [
      'data' => $mediaData,
    ]);
    
    $this->container->view->getEnvironment()->addGlobal('settings', [
      'name' => 'Edit media',
      'title' => 'Media Management',
      'breadcrumb' => [
        ['name' => 'Media', 'path' => 'admin.media.all']
      ],
    ]);
    
    return $this->view->render($response, 'admin/media/edit.html');    
  }
  
  public function postEdit($request, $response, $arguments)
  {
    $media = Media::firstOrCreate(['id' => $arguments['uid']]);
    
    if(!$media) {
      $this->flash->addMessage('error', "No media with ID '{$arguments['uid']}' found.");
      return $response->withRedirect($this->router->pathFor('admin.media.all'));     
    }
    
    $hasUpload = $request->getUploadedFiles();
        
    if( isset( $hasUpload['file']) ) {
      self::uploadFile($hasUpload, $media);
    } else {
      $this->flash->addMessage('error', "No file selected for upload.");
      return $response->withRedirect($this->router->pathFor('admin.media.all'));     
    }
    
    $credentials = [
      'name' => $request->getParam('name'),
      'description' => $request->getParam('description'),
      'publish_at' => $request->getParam('publish_at'),
      'unpublish_at' => $request->getParam('unpublish_at'),
      'visible_to' => $request->getParam('visible_to'),
      'note' => $request->getParam('note'),
    ];
    
    // update data
    if($media->update($credentials)) {
      $this->flash->addMessage('success', "Media details for '{$credentials['name']}' have been changed.");
    } else {
      $this->flash->addMessage('error', "The media could not be updated.");
    }
    return $response->withRedirect($this->router->pathFor('admin.media.all'));
  }
  
  public function delete($request, $response, $arguments)
  { 
  }
  
  public function convertPortrait($inputfile, $outputfile) {
  // input/output should be COMPLETE absolute path+filename
    $command = sprintf('convert %s -auto-orient -gravity center -resize 270x270^ -crop 270x270+0+0 +repage -set colorspace Gray -separate -average %s', $inputfile, $outputfile);
    exec($command);
    
  #convert $outputfile \( -clone 0 -fill "#00FF00" -colorize 10 \) -compose multiply -composite $outputfile
  }
}