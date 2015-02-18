<?php

namespace Controllers\PublicSite;

class BillingChargeController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {

		$this->authenticate();

		// Set your secret key: remember to change this to your live secret key in production
		// See your keys here https://dashboard.stripe.com/account
		\Stripe::setApiKey($this->app->config->stripe->secret_key);

		// Create the charge on Stripe's servers - this will charge the user's card
		try {
			$token = $this->req->getPost('token');
			$data = array(
				"amount" => ($this->event['price']*100),
				"currency" => $this->event['currency'],
				"card" => $token['id'],
				"description" => "Ticket for ".$this->person['email'],
				"receipt_email" => $this->person['email']
			);
			$charge = \Stripe_Charge::create($data);

			$this->app->db->query('UPDATE attendance SET ticket_type=%s, ticket_date=NOW(), ticket_id=%s WHERE person_id=%d AND event_id=%d', 'Standard', $charge->id, $this->person['id'], $this->event['id']);

			$this->resp->setJSON(true);
		} catch(\Stripe_CardError $e) {
			$this->resp->setJSON($e->getMessage());
		}
	}
}
