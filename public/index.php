<?php

require __DIR__."/../vendor/autoload.php";


/* Load services into dependency injection container */

$app = new ServicesContainer();

if (isset($_SERVER['HTTP_DEBUG']) and $_SERVER['HTTP_DEBUG'] == $app->config->debug->debug_key) {
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
}


/* Define routing and dispatch controllers to build response */

$router = new Routing\Router($app);

// Set URL slug patterns
$router->setPattern('eventslug', '\d{4}\-\w+');
$router->setPattern('id', '\d+');

// Authentication routes
$router->route('/auth/callback', 'AuthCallback');
$router->route('/auth/logout', 'AuthLogout');

// Public content routes
$router->route('/', 'PublicSite\Info');
$router->route('/:eventslug', 'PublicSite\Info');
$router->route('/:eventslug/(?<page>schedule|faq)', 'PublicSite\Info');
$router->route('/:eventslug/register', 'PublicSite\Register');
$router->route('/:eventslug/video', 'PublicSite\VideoAPI');
$router->route('/:eventslug/video/(?<video_id>[\w\d\-\_]+)', 'PublicSite\VideoAPI');

// Admin routes
$router->route('/admin', '/admin/people');
$router->route('/admin/people', 'Admin\People');
$router->route('/admin/people/:id', 'Admin\Person');
$router->route('/admin/panels', 'Admin\Panels');
$router->route('/admin/invite', 'Admin\Invite');
$router->route('/admin/rate', 'Admin\Rate');
$router->route('/admin/badges', 'Admin\Badges');

$router->route('/errortest', 'ErrorTest');

$router->errorUnsupportedMethod('Errors\Error405');
$router->errorNoRoute('Errors\Error404');

$req = Routing\Request::createFromGlobals();
$resp = new Routing\Response();

$router->dispatch($req, $resp);


/* Serve the response */

$resp->serve();
