<?php

namespace Controllers\PublicSite;

class PublicBaseController extends \Controllers\BaseController {

	protected $event;

	public function initialise() {
		if (!empty($this->routeargs['eventslug'])) {
			$this->event = $this->app->db->queryRow('SELECT * FROM events WHERE slug=%s', $this->routeargs['eventslug']);

			// If the requested event doesn't exist, cancel the route to cause a 404
    		if (!$this->event) return false;

    		$this->event['ticketsavailable'] = (boolean)$this->app->db->querySingle('SELECT 1 FROM events e WHERE e.id=%d AND e.start_time > NOW() AND (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.ticket_type IS NOT NULL) < e.capacity', $this->event['id']);

			$this->addViewData('thisevent', $this->event);
		}

		// Also add details of the next event (may be the same)
		$event = $this->app->db->queryRow('SELECT * FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');
		$this->addViewData('nextevent', $event);
	}
}
