<?php
/**
 * Route rejected exception
 *
 * Issued by a controller that has been dispatched but cannot handle the request.  The route that invoked the controller should be considered non-matching for this request
 */

namespace Routing;

class RouteRejectedException extends \Exception {}
