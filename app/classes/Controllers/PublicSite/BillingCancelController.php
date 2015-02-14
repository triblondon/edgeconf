<?php

namespace Controllers\PublicSite;

class BillingCancelController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {

		$this->authenticate();

		$attendance = $this->app->db->queryRow('SELECT * FROM attendance WHERE person_id=%d AND event_id=%d', $this->person['id'], $this->event['id']);

		// Set your secret key: remember to change this to your live secret key in production
		// See your keys here https://dashboard.stripe.com/account
		\Stripe::setApiKey($this->app->config->stripe->secret_key);

		try {
			$ch = \Stripe_Charge::retrieve($attendance['ticket_id']);
			$re = $ch->refunds->create();

			$this->app->db->query('DELETE FROM attendance WHERE person_id=%d AND event_id=%d', $this->person['id'], $this->event['id']);

			$this->resp->setJSON(true);
		} catch(\Stripe_CardError $e) {
			$this->resp->setJSON($e->getMessage());
		}
	}
}
