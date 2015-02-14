<?php

namespace Controllers\PublicSite;

class AuthEmailSendCodeController extends \Controllers\PublicSite\PublicBaseController {

    public function post() {

		$code = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890'), 0, 9);

		// Stash it in the session
		@session_start();
		if (empty($_SESSION['emailverify'])) $_SESSION['emailverify'] = array();
		$_SESSION['emailverify'][$this->req->getPost('email')] = $code;

		$viewdata = array(
			'code' => $code,
			'email' => $this->req->getPost('email'),
			'event' => $this->nextevent
		);

		$this->sendEmail(
			$this->req->getPost('email'),
			'Verify email using '.$code,
			$this->app->view->render('emails/emailverify.txt', $viewdata),
			$this->app->view->render('emails/emailverify.html', $viewdata)
		);
    }
}
