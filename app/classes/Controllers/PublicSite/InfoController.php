<?php

namespace Controllers\PublicSite;

class InfoController extends \Controllers\PublicSite\PublicBaseController {

	public function get() {

		if (!$this->event) {
			$slug = $this->app['db']->querySingle('SELECT slug FROM events WHERE end_time > NOW() ORDER BY start_time ASC LIMIT 1');
			$this->resp->redirect('/'.$slug);
			return;

		} else if (empty($this->routeargs['page'])) {
			$templ = 'home';

			$this->addViewData(array(
				'livesession' => $this->app->db->queryRow("SELECT * FROM sessions WHERE start_time < NOW() AND end_time > NOW() AND event_id=%d", $this->event['id']),
				'sessions' => $this->app->db->queryAllRows("SELECT name, start_time, end_time, youtube_id FROM sessions WHERE event_id=%d ORDER BY start_time", $this->event['id']),
			));

		} else if ($this->routeargs['page'] == 'schedule') {
			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE event_id=%d ORDER BY start_time', $this->viewdata['thisevent']['id']);
			foreach ($sessions as &$session) {
				$session['panelists'] = $this->app->db->queryAllRows('SELECT pe.given_name, pe.family_name, pe.org, pe.bio, par.role FROM people pe INNER JOIN participation par ON pe.id=par.person_id WHERE par.session_id=%d AND role IN (%s, %s, %s) AND panel_status=%s ORDER BY role=%s DESC, role=%s DESC', $session['id'], 'Moderator', 'Panelist', 'Speaker', 'Confirmed', 'Moderator', 'Speaker');
			}
			$this->addViewData('sessions', $sessions);
			$templ = 'schedule';

		} else if ($this->routeargs['page'] == 'hub') {
			$templ = 'hub';

		} else if ($this->routeargs['page'] == 'faq') {
			$this->addViewData('faqs', $this->app->db->queryAllRows('SELECT * FROM faqs WHERE event_id=%d OR event_id IS NULL', $this->viewdata['thisevent']['id']));
			$templ = 'faq';
		}

		$this->renderView($templ);
	}

}
