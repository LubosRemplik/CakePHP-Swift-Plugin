<?php

App::import('Vendor', 'Swift', array(
	'file' => 'swift' . DS . 'lib' . DS . 'swift_required.php', 
	'plugin' => 'swift'
));

/**
 * Swift mailer component
 * @version 1.0
 * @author Tsuyoshi Saito <tsuyoshi@on-idle.com>
 */
class SwiftComponent extends Object {
    var $controller = null;
    var $failures;
    
	function initialize(&$controller, $settings = array()) {
		$this->controller = $controller;
	}
	
    function startup(&$controller) {
        $this->controller = $controller;
    }
    
    /**
     * Sending email message
     * $this->Swift->send(array(
     *     'subject' => 'Your mail subject line',
     *     'from' => array('tsuyoshi@on-idle.com' => 'Tsuyoshi Saito'),
     *     'to' => array('tsuyoshi@on-idle.com' => 'Tsuyoshi Saito'),
     *     'textBody' => 'Text email body text',
     *     'htmlBody' => '<p>HTML email body text</p>'
     * ));
     * @return integer
     * @param array $options[optional]
     */
    public function send($options = array()) {
        extract($options);
        // Swiftmailer
        $mailer = $this->getMailer();
        // Message object
        $message = Swift_Message::newInstance($subject)
            ->setFrom($from)
            ->setTo($to)
            ;
        if(!empty($cc)) $message->setCc($cc);
        if(!empty($bcc)) $message->setBcc($bcc);
        // Text message
        if(!empty($textBody)) {
            $textBody = str_replace(array("\r\n", "\r"), "\n", $textBody);
            $message->setBody($textBody);
        }
        // HTML message
        if(!empty($htmlBody)) $message->addPart($htmlBody, "text/html");
        // Sending message
        return $mailer->batchSend($message, $this->failures);
    }
    
    /**
     * Render email message
     * @return string
     * @param string $viewFile
     * @param array $viewVars[optional]
     * @param array $options[optional]
     */
    public function render($viewFile, $viewVars = array(), $options = array()) {
        // Creating new controller to render email view
        App::import('Core', array('Controller', 'Router'));
        $controller = new Controller;
        if(!$this->controller) $this->controller = $controller;
        // Setting controller property
        $helpers = array('Html');
        $property = array(
            'helpers' => isset($this->controller->helpers) ? am($helpers, $this->controller->helpers) : $helpers,
            'view' => isset($this->controller->view) ? $this->controller->view : 'View',
            'theme' => isset($this->controller->theme) ? $this->controller->theme : null,
            'plugin' => isset($this->controller->plugin) ? $this->controller->plugin : null,
            'layout' => null, // Layout is null as default
            'viewPath' => isset($this->controller->viewPath) ? $this->controller->viewPath : 'email'
        );
        $property = am($property, $options);
        extract($property);
        $controller->view = $view;
//        $controller->helpers = $helpers;
        $controller->theme = $theme;
        $controller->plugin = $plugin;
        $controller->layout = $layout;
        $controller->viewPath = $viewPath;
        $controller->set($viewVars);
        $controller->render($viewFile);
        return $controller->output;
    }
    
    /**
     * Set mailer backend
     * @return object SwiftMailer
     */
    private function getMailer() {
        // Load Swift mailer library
        $config = !Configure::read('ServerMailer.backend') ? 
			array('backend' => 'mail') 
			: Configure::read('ServerMailer');
        
		switch($config['backend']) {
            case "smtp":
                $host = !empty($config['options']['host']) ? $config['options']['host'] : 'localhost';
                $port = !empty($config['options']['port']) ? $config['options']['port'] : 25;
                $transport = Swift_SmtpTransport::newInstance($host, $port);
                if(!empty($config['options']['username'])) 
                    $transport->setUsername($config['options']['username']);
                if(!empty($config['options']['password'])) 
                    $transport->setPassword($config['options']['password']);
                break; 
            case "sendmail":
                $path = !empty($config['options']['path']) ? 
                $config['options']['path'] : '/usr/sbin/exim -bs';
                $transport = Swift_SendmailTransport::newInstance($path);
                break;
            case "mail":
                $transport = Swift_MailTransport::newInstance();
                break;
        }
        //Create the Mailer using your created Transport
        return Swift_Mailer::newInstance($transport);
    }
    
    public function getFailures() {
        return $this->failures;
    }
    
    /**
     * Parse email text in format 
     * Luboš Remplík <lubos@on-idle.com>, smith@email.com
     * to array suite for recepients
     */
    public function parseRecepients($data = null) {
    	if($data) {
    		$return = array();
            $recipients = explode(',', $data);
            foreach($recipients as $value) {
                $recipient = explode(' ', trim($value));
                $email = trim(array_pop($recipient), '<>');
                $name = implode(' ', $recipient);
                $return[$email] = $name;
            }      
            return $return;
    	}
    	return false;    	
    }
}

?>