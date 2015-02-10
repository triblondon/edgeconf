<?php

namespace Controllers;

class AuthEmailSendCodeController extends \Controllers\BaseController {

    public function post() {

    	$code = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890'), 0, 9);

    	// Stash it in the session
		@session_start();
		if (empty($_SESSION['emailverify'])) $_SESSION['emailverify'] = array();
		$_SESSION['emailverify'][$this->req->getPost('email')] = $code;

		// Send the email
		/*$mg = new \Mailgun\Mailgun($this->app->config->mailgun->api_key);
		$mg->sendMessage('edgeconf.com', array(
			'from' => 'Edge conf <hello@edgeconf.com>',
			'to' => $this->req->getPost('email'),
			'subject' => 'Verify email using '.$code,
			'text' => $this->app->view->render('emails/emailverify.txt', array(
				'code' => $code,
				'email' => $this->req->getPost('email')
			))
		));*/

		$email = new \SendGrid\Email();
		$email->addCategory('Email verify');
		$email->addTo($this->req->getPost('email'));
		$email->setFrom('hello@edgeconf.com');
		$email->setFromName('Edge conf');
		$email->setSubject('Verify email using '.$code);
		$email->setText($this->app->view->render('emails/emailverify.txt', array(
			'code' => $code,
			'email' => $this->req->getPost('email')
		)));

		$sendgrid = new \SendGrid($this->app->config->sendgrid->username, $this->app->config->sendgrid->password);
		$resp = $sendgrid->send($email);
    }
}
