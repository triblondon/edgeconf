<?php

use Carbon\Carbon;

class ServicesContainer extends Pimple {

	function __construct() {

		parent::__construct();

		$this['config'] = function($c) {
			return new Config(__DIR__."/../config.ini");
		};

		$this['db'] = function($c) {
			$db = new \Services\MySQL\MySqlConnection(
				$c->config->mysql->host,
				$c->config->mysql->username,
				$c->config->mysql->password,
				$c->config->mysql->db_name
			);
			$db->setTimeZone("+00:00");
			return $db;
		};

		$this['eb'] = function($c) {
			return new \Services\Eventbrite\Eventbrite(array(
				'app_key' => $c->config->eventbrite->appkey,
				'user_key' => $c->config->eventbrite->userkey
			));
		};

		$this['mailchimp'] = function($c) {
			return new \Services\Mailchimp\MCAPI($c->config->mailchimp->api_key);
		};

		$this['view'] = function($c) {
			$loader = new Twig_Loader_Filesystem(realpath(__DIR__.'/../../app/views'));
			$twig = new Twig_Environment($loader, array(
				'cache' => realpath(__DIR__.'/../../'.$c->config->view->cache_path),
				'debug' => true
			));
			$twig->addFilter(new Twig_SimpleFilter('slugify', function ($string) {
				return strtolower(str_replace(' ', '-', $string));
			}));
			$twig->addFilter(new Twig_SimpleFilter('tourl', function ($string) {
				return rawurlencode(str_replace('\n', ',', $string));
			}));
			$twig->addFilter(new Twig_SimpleFilter('timeago', function ($date) {
				return Carbon::instance($date)->diffForHumans();
			}));
			$twig->addFilter(new Twig_SimpleFilter('jsdates', function ($thing) {
				function recurseDates($thing) {
					if (is_array($thing)) {
						foreach($thing as $k => $subthing) $thing[$k] = recurseDates($subthing);
					} elseif ($thing instanceOf DateTime) {
						$thing = (integer)$thing->format('U');
					}
					return $thing;
				}
				return recurseDates($thing);
			}));
			$twig->addGlobal('server', array(
				'request_uri' => $_SERVER['REQUEST_URI']
			));
			$twig->addGlobal('layout', array(
				'allevents' => $c->db->queryAllRows('(SELECT * FROM events WHERE end_time < NOW()) UNION (SELECT * FROM events WHERE end_time > NOW() ORDER BY end_time LIMIT 1) ORDER BY start_time DESC')
			));
			$twig->addExtension(new Twig_Extension_Debug());
			return $twig;
		};

		$this['auth'] = function($c) {
			if (!session_id()) session_start();
			$host = $_SERVER['HTTP_HOST'];
			return new \Services\GoogleAuth\GoogleAuth(
				$_SESSION,
				$c->config->google->client_id,
				$c->config->google->secret,
				array(
					'canceldest' => 'http://edgeconf.com/',
					'callback' => $c->config->google->callback
				)
			);
		};

		$this['sentry'] = function($c) {
			return new Raven_Client($c->config->sentry->dsn, array(
				'tags' => array(
					'php_version' => phpversion(),
				)
			));
		};
	}
}
