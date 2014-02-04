<?php

namespace Controllers\PublicSite;

class VideoAPIController extends \Controllers\PublicSite\PublicBaseController {

    public function get() {

		if (empty($this->routeargs['video_id'])) {
			$this->resp->setJSON(
				$this->app->db->queryLookupTable('SELECT youtube_id as k, name as v FROM sessions WHERE youtube_id IS NOT NULL AND event_id=%d', $this->event['id'])
			);

		} else {
			$this->resp->setHeader('Content-Type', 'application/x-subrip');
			$this->resp->setContent(
				$this->app->db->querySingle('SELECT youtube_srt_captions FROM sessions WHERE event_id=%d AND youtube_id=%s', $this->event['id'], $this->routeargs['video_id'])
			);
		}

    }
}
