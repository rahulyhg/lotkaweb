<?php
namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use App\Mail\Templater;
  
class Sender
{
  private $settings;

  private $mail;

  public function __construct($settings)
  {
    $this->settings = $settings;
    $this->mail = new PHPMailer($settings['renderer']['debug']);
  }

  private function setupSMTP() {
    $email_settings = $this->settings['mail'];
    $mail = $this->mail;

    //Server settings
    $mail->SMTPDebug = 2;                                   // Enable verbose debug output
    $mail->isSMTP();                                        // Set mailer to use SMTP
    $mail->Host = $email_settings['smtp']['server'];        // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                                 // Enable SMTP authentication
    $mail->Username = $email_settings['smtp']['email'];     // SMTP username
    $mail->Password = $email_settings['smtp']['password'];  // SMTP password
    $mail->SMTPSecure = 'tls';                              // Enable TLS encryption, `ssl` also accepted
    $mail->Port = $email_settings['smtp']['port'];;         // TCP port to connect to
  }

  public function send($recipient, $subject, $body_template, $vars = array()) {
    $email_settings = $this->settings['mail'];
    $mail = $this->mail;
    
    try {
      $this->setupSMTP();
      //Recipients
      $mail->setFrom(
        $email_settings['from']['email'], 
        $email_settings['from']['name']
      );
      
      $body = new Templater($body_template, $vars);
      
      $mail->addAddress($recipient);
      $mail->Subject = $subject;
      $mail->Body    = $body;

      $mail->send();
      return true;
    } catch (Exception $e) {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
    return false;
  }
}