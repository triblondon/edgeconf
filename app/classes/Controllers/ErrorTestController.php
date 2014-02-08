<?php
/**
 * Generate a PHP error
 *
 * Use to test that you have correctly set up the debugging header (if you have, it returns a full stack trace, otherwise an empty file)
 */

namespace Controllers;

class ErrorTestController extends \Controllers\BaseController {

	public function get() {

		$a = 1/0;

	}
}
