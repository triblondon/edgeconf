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

		} else if ($this->routeargs['page'] == 'schedule') {
			$sessions = $this->app->db->queryAllRows('SELECT * FROM sessions WHERE event_id=%d ORDER BY start_time', $this->viewdata['thisevent']['id']);
			foreach ($sessions as &$session) {
				$session['panelists'] = $this->app->db->queryAllRows('SELECT pe.given_name, pe.family_name, pe.org, pe.bio, par.role FROM people pe INNER JOIN participation par ON pe.id=par.person_id WHERE par.session_id=%d AND role IN (%s, %s) AND panel_status=%s ORDER BY role=%s DESC', $session['id'], 'Moderator', 'Panelist', 'Confirmed', 'Moderator');
			}
			$this->addViewData('sessions', $sessions);
			$templ = 'schedule';

		} else if ($this->routeargs['page'] == 'faq') {
			$this->addViewData('faqs', $this->app->db->queryAllRows('SELECT * FROM faqs WHERE event_id=%d OR event_id IS NULL', $this->viewdata['thisevent']['id']));
			$templ = 'faq';
		}

    	$this->renderView($templ);
    }
}
