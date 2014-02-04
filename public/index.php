<?php

require __DIR__."/../vendor/autoload.php";


/* Load services into dependency injection container */

$app = new ServicesContainer();


/* Define routing and dispatch controllers to build response */

$router = new Routing\Router($app);

// Set URL slug patterns
$router->setPattern('eventslug', '\d{4}\-\w+');
$router->setPattern('page', 'schedule|faq');
$router->setPattern('id', '\d+');
$router->setPattern('video_id', '[\w\d\-\_]+');

// Authentication routes
$router->route('/auth/callback', 'AuthCallback');
$router->route('/auth/logout', 'AuthLogout');

// Public content routes
$router->route('/', 'PublicSite\Info');
$router->route('/:eventslug', 'PublicSite\Info');
$router->route('/:eventslug/:page', 'PublicSite\Info');
$router->route('/:eventslug/register', 'PublicSite\Register');
$router->route('/:eventslug/video', 'PublicSite\VideoAPI');
$router->route('/:eventslug/video/:video_id', 'PublicSite\VideoAPI');

// Admin routes
$router->route('/admin', '/admin/people');
$router->route('/admin/people', 'Admin\People');
$router->route('/admin/people/:id', 'Admin\Person');
$router->route('/admin/panels', 'Admin\Panels');
$router->route('/admin/invite', 'Admin\Invite');
$router->route('/admin/rate', 'Admin\Rate');
$router->route('/admin/badges', 'Admin\Badges');

$router->errorUnsupportedMethod('Errors\Error405');
$router->errorNoRoute('Errors\Error404');

$req = Routing\Request::createFromGlobals();
$resp = new Routing\Response();

$router->dispatch($req, $resp);


/* Serve the response */

$resp->serve();
