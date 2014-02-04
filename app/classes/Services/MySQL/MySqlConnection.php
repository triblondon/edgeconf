<?php
/**
 * Class for connection to mysql database
 *
 * @codingstandard Assanka
 * @author Luke Blaney <luke.blaney@assanka.net>
 * @copyright Assanka Limited [All rights reserved]
 */

namespace Services\MySQL;

class MySqlConnection {

	protected $server, $username, $password;
	private $dbname;
	private $connectionOpened = false;
	private $conn;
	private $mode, $args;
	protected $reconnectOnFail = false;
	protected $logQueries = false;
	protected $queryLog = array();
	protected $suppressErrors = false;
	private $queryCount = 0;

	const CR_SERVER_GONE = 2006;
	const CR_SERVER_LOST = 2013;
	const ER_LOCK_WAIT_TIMEOUT = 1205;
	const ER_LOCK_DEADLOCK = 1213;


	/**
	 * Creates a new MySqlConnection instance, but dosen't connect until needed.
	 *
	 * @param string $server   The server to connect to
	 * @param string $username The username to use for authentication
	 * @param string $password The password to use for authentication
	 * @param string $dbname   The name of the database to connect to
	 * @return void
	 */
	public function __construct($server, $username, $password, $dbname) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->dbname = $dbname;
	}

	/**
	 * Connect to MySQL server
	 *
	 * Creates a connection using the connection settings defined
	 * when the MySQLConnection object was created.
	 *
	 * @return void
	 */
	private function _connect() {
		$this->conn = new \mysqli($this->server, $this->username, $this->password, $this->dbname);
		if ($this->conn->connect_error) {

			// Try exactly once more to reconnect, so as to mitigate temporary problems discussed in helpdesk #15410
			sleep(1);
			$this->conn = new \mysqli($this->server, $this->username, $this->password, $this->dbname);
			if (!$this->conn) throw new \Exception("Database server could not be contacted");
		}
		$this->conn->set_charset('utf8');
		$this->runQuery("SET AUTOCOMMIT = 1");
		$this->connectionOpened = true;
	}

	/**
	 * Returns whether or not the connection has already been opened
	 *
	 * @return bool
	 */
	protected function isConnected() {
		return $this->connectionOpened;
	}

	/**
	 * Internal method that actually performs a query
	 *
	 * Runs a query on the MySQL server.
	 *
	 * @param string $queryExpr SQL query to execute
	 * @return FALSE on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries returns a result object. For other successful queries returns TRUE.
	 */
	private function runQuery($queryExpr) {
		return $this->conn->query($queryExpr);
	}

	/**
	 * Perform a query
	 *
	 * Runs a query on the MySQL server.  Initiates a connection if one is not already present.  Adds query to a query log if querylogging is enabled.  If you wish to execute your query with no parsing, call query() with one parameter - the query to run.
	 *
	 * Two forms of prepared statement are supported.  In the first, placeholders are specified as {key}, where key matches a key in an associative array of data which you pass to query as the second argument.  The keys should also, where possible, match the column names in your database, since a simple {foo} placeholder will become "foo = 'value_of_foo'".  By default, all placeholders written in this form will expand to include a key and an equality/assignment operator.  You can alter this behaviour by using modifiers, which are appended to the placeholder using a pipe, inside the braces, eg {foo|date}.  This form of prepared statement is suitable when you have a keyed array of data, often from a web form, and the keys match the columns in your database.
	 *
	 * The second form of prepared statement uses sprintf syntax.  Placeholders look like %s (the full sprintf vocabulary is supported, see http://www.php.net/sprintf for details), but modifiers can be used by appending a pipe and the modifier name, eg, %s|date.  The data for the sprintf prepared statement is passed to query() in the second and subsequent parameters, one value per argument, and are replaced into the query in the order in which they are passed in.  This form of prepared statement is suitable when you have several items of data from different sources that you wish to combine for a single query, or where you wish to take advantage of sprintf functionality.
	 *
	 * Both forms of prepared statement support modifiers.
	 * Multiple modifiers are supported and should be separated by underscores.  Modifiers which take an array as input (list and values) are handled first.  Other modifiers are handled in the order they are used (from left to right).
	 *
	 * The following modifiers are available:
	 *
	 * * date: Interpret the input as a date and convert it to MySQL date format
	 * * utcdate: Interpret the input as a date and convert it to MySQL date format using Universal Coordinated Time
	 * * like: Interpret the input as a string, escape it, wrap it with '%...%'.  Use LIKE operator.
	 * * 01: Interpret input as a boolean, and convert it to a 0 or 1.
	 * * nokey: Don't display key or operator.  (has no effect for sprintf syntax).
	 * * isnull: If the value equals null, replace equality operator with 'IS'. For use in conditional statements.
	 * * list: Iterpret the input as an array and display it as 'IN (...)'
	 * * values: Interpret the input as an array of arrays and display it as 'VALUES ((...), (...) ...)'
	 * * tostring: Converts the input to a string.
	 * * not: Replace equality/assignment operator with <>
	 * * >: Replace equality/assignment operator with >
	 * * >=: Replace equality/assignment operator with >=
	 * * <: Replace equality/assignment operator with <
	 * * <=: Replace equality/assignment operator with <=
	 *
	 * @param string $query  SQL query to execute
	 * @param mixed  $params If you wish to format a prepared statement, the second parameter may be a keyed associative array of data, keys must correspond to keys specified in querystr.  Alternatively if using sprintf syntax, sprintf variables are passed as the 2nd, 3rd, 4th and subsequent arguments to this method.
	 * @return MySqlResult
	 */
	public function query() {
		if (!$this->isConnected()) $this->_connect();
		$args = func_get_args();
		$queryExpr = call_user_func_array(array($this, 'parse'), $args);
		if (empty($queryExpr)) throw new \Exception("Query is empty");

		$start = microtime(true);
		$resultObject = $this->runQuery($queryExpr);
		$end = microtime(true);

		$resultDetails = array(
			'queryExpr' => $queryExpr,
			'affectedRows' => $this->conn->affected_rows,
			'insertId' => $this->conn->insert_id,
			'timeTaken' => $end - $start,
			'dateExecuted' => $start,
			'errorNo' => $this->conn->errno,
			'errorMsg' => $this->conn->error,
		);

		$this->queryCount++;
		if ($this->logQueries) $this->logQuery($resultDetails);

		if (!$resultObject and $this->reconnectOnFail and ($resultDetails['errorNo'] == self::CR_SERVER_LOST or $resultDetails['errorNo'] == self::CR_SERVER_GONE)) {
			$this->connectionOpened = false;
			sleep(5);
			return call_user_func_array(array($this, "query"), func_get_args());
		}
		if (!$resultObject and ($resultDetails['errorNo'] == self::ER_LOCK_WAIT_TIMEOUT)) {

			// For debug, get full processlist
			$proclistres = $this->runQuery("SHOW FULL PROCESSLIST");

			// Made global so it's easier to find in Error Handler
			global $mysqldebug_proclist;

			// If the fetch_all method exists (requires mysqlnd), then use it.
			if (method_exists($this->result, 'fetch_all')) {
				$mysqldebug_proclist = $this->result->fetch_all(MYSQLI_ASSOC);
			} else {
				$mysqldebug_proclist = array();
				while ($row = $proclistres->fetch_assoc()) $mysqldebug_proclist[] = $row;
			}
			throw new \Exception("MySQL lock wait timeout");
		}
		if (!$resultObject and $resultDetails['errorNo'] == self::ER_LOCK_DEADLOCK) {

			// If deadlock found, retry once.
			static $deadlockRetry;
			if (!isset($deadlockRetry)) {
				$deadlockRetry = 1;
				sleep(5);
				return call_user_func_array(array($this, "query"), func_get_args());
			}
		}
		if (!$resultObject and !$this->suppressErrors) {
			$this->runQuery("ROLLBACK");
			throw new \Exception($resultDetails['errorMsg']." (".$resultDetails['errorNo'].") occured in query: ".$queryExpr);
		}
		return new MySqlResult($resultObject, $resultDetails);
	}




	/**
	 * Construct a complete query from a prepared statement using either form of prepared statement.
	 *
	 * For argument list, see query().
	 *
	 * @return string Complete query
	 */
	public function parse() {
		if (!func_num_args()) throw new \Exception("No query specified");
		$this->args = func_get_args();
		$queryExpr = array_shift($this->args);

		if ((strpos($queryExpr, "'") !== false or strpos($queryExpr, "\"") !== false)) trigger_error("Literal strings should not be included in queries.  Use a prepared statement.", E_USER_DEPRECATED);

		// Determine which type of prepared statement is being used
		if (count($this->args) == 1 and is_array($this->args[0]) and ($this->args[0] != array_values($this->args[0]))) {
			$this->args = $this->args[0];
			$this->mode = 'bykey';
			$pattern = '/{(.+?)(\|.+?)?}/';
		} elseif (count($this->args) >= 1) {
			$this->mode = 'sprintf';
			$pattern = '/(%\w)(\|[\w<>=]+)?/';
		} else {
			$this->mode = 'unprepared';
		}

		if (isset($pattern)) $queryExpr = preg_replace_callback($pattern, array(&$this, 'replacePlaceholder'), $queryExpr);
		return $queryExpr;

	}

	/**
	 * Takes a placeholder and replaces it with an appropriate value based on which mode of prepared statement being used.
	 *
	 * $this->mode and $this->args must be set before calling
	 *
	 * @param array $match An array of matches from preg
	 * @return string
	 */
	private function replacePlaceholder($match) {
		$modifierString = (isset($match[2])) ? trim($match[2], '|_ ') : null;
		if (empty($modifierString)) $modifiers = array();
		else $modifiers = explode('_', strtolower($modifierString));
		if ($this->mode == 'sprintf') {
			if (!count($this->args)) throw new \Exception("Not enough parameters");
			$value = array_shift($this->args);
			$sprintfFormat = trim($match[1], '%');
			$key = null;
		} else {
			$key = $match[1];

			// Use array_key_exists instead of isset because isset returns false if value is null
			if (!array_key_exists($key, $this->args)) throw new \Exception("Can't find argument for '$key'");
			$value = $this->args[$key];
			$sprintfFormat = null;
		}
		return $this->parseValue($key, $value, $modifiers, $sprintfFormat);
	}

	/**
	 * Returns an encoded and appropiately modifed string for insertion into an sql query
	 *
	 * @param string $key           The name of the field
	 * @param mixed  $value         The value to insert
	 * @param array  $modifiers     An array of modifiers to apply to the value
	 * @param string $sprintfFormat The format for sprintf to use when formatting the string. If null, sprintf isn't used.
	 * @return string
	 */
	private function parseValue($key, $value, array $modifiers = null, $sprintfFormat = null) {
		if (is_null($modifiers)) $modifiers = array();
		$oper = '=';

		// The $beenEncoded flag should be updated any time a modifer changes $value.
		$beenEncoded = false;

		// Look for any modifiers which require an array as input first (these modifiers are mutually exclusive, so just act on the last one.)
		$arrayModifiers = array('list', 'values');
		$arrayMod = null;
		foreach ($arrayModifiers as $mod) if (in_array($mod, $modifiers)) $arrayMod = $mod;
		if ($arrayMod) {
			$value = (array)$value;
			$subModifiers = array_diff($modifiers, $arrayModifiers);
			if ($arrayMod == 'values') $subModifiers[] = 'list';

			foreach ($value as &$subval) $subval = $this->parseValue(null, $subval, $subModifiers, $sprintfFormat);
			switch ($arrayMod) {
				case 'list':
					$oper = 'IN';
					$value = '('.implode(', ', $value).')';
					break;
				case 'values':
					$oper = 'VALUES';
					$value = implode(', ', $value);
					break;
			}

			// All other modifiers (except nokey) should be applied to subvalues of the list, rather than the list output
			if (in_array('nokey', $modifiers)) $modifiers = array('nokey');
			else $modifiers = array();
			$sprintfFormat = null;
			$beenEncoded = true;
		}

		// Handle other modifiers in the order they are used
		foreach ($modifiers as $modifier) {
			switch ($modifier) {
				case 'nokey':
					$key = null;
					break;
				case 'date':
				case 'utcdate':
					$value = self::parseDate($value);
					$beenEncoded = false;
					if (!$value) {
						$value = null;
						break;
					}
					if ($modifier == 'utcdate') $value->setTimezone(new \DateTimeZone("UTC"));
					$value = $value->format("Y-m-d H:i:s");
					break;
				case '10':
				case '01':
				case 'bool':
					$value = $value ? 1 : 0;
					$beenEncoded = false;
					break;
				case 'isnull':
					if (is_null($value)) $oper = "IS";
					break;
				case 'like':
					$oper = "LIKE";
					$value = "'%".str_replace(array('%', '_'), array('\%', '\_'), $this->sqlenc($value))."%'";
					$beenEncoded = true;
					break;
				case 'tostring':
					if (!is_null($value)) $value = (string)$value;
					$beenEncoded = false;
					break;
				case 'not':
					$oper = '<>';
					break;
				case '<':
				case '>':
				case '<=':
				case '>=':
				case '<>':
					$oper = $modifier;
					break;
				default:
					trigger_error("Modifier '$modifier' not found.", E_USER_NOTICE);
			}
		}

		if (!is_null($value) and !is_scalar($value) and !(is_object($value) and method_exists($value, '__toString'))) throw new \Exception("Can't convert value to string");

		if ($sprintfFormat and !is_null($value)) $value = sprintf('%'.$sprintfFormat, $value);

		if (is_null($value)) $value = "NULL";
		elseif ((is_string($value) or (is_object($value) and method_exists($value, '__toString'))) and !$beenEncoded and (!$sprintfFormat or strpos("ceEgGxXs", $sprintfFormat) !== false)) $value = "'".$this->sqlenc((string)$value)."'";

		if (is_null($key)) return $value;
		return "`$key` $oper $value";
	}

	/**
	 * Escapes special characters in a string for use in a SQL statement
	 *
	 * Escapes special characters in the unescaped string, taking into
	 * account the current character set of the connection so that it is
	 * safe to place it in a query.  Binary safe.
	 *
	 * Calls MySQL's library function mysql_real_escape_string, which prepends
	 * backslashes to the following characters: \x00, \n, \r, \, ', " and \x1a.
	 *
	 * @param mixed $val String to escape
	 * @return mixed Escaped version of input
	 */
	private function sqlenc($val) {
		if (!$this->isConnected()) $this->_connect();
		return $this->conn->real_escape_string($val);
	}

	/**
	 * Sets whether or not the connection should reconnect in the event of a failure
	 *
	 * @param bool $newval whether or not to reconnect
	 * @return void
	 */
	public function setReconnectOnFail($newval = true) {
		$this->reconnectOnFail = (bool)$newval;
	}

	/**
	 * Sets whether or not errors should be suppressed
	 *
	 * @param bool $newval whether or not to suppress errors
	 * @return void
	 */
	public function setErrorSuppression($newval) {
		$this->suppressErrors = (bool)$newval;
	}

	/**
	 * Sets whether or not queries should be logged
	 *
	 *	NB: Turning off logging will not clear existing query log.  To do this use clearQueryLog()
	 *
	 * @param bool $newval whether or not to log queries
	 * @return void
	 */
	public function setQueryLogging($newval ) {
		$this->logQueries = (bool)$newval;
	}

	/**
	 * Change database
	 *
	 * @param string $dbname Name of database to select
	 * @return void
	 */
	public function changeDatabase($dbname ) {
		$this->dbname = $dbname;
		if ($this->isConnected()) {
			if (!$this->conn->select_db($dbname)) throw new \Exception("Database was not found");
		} else {
			$this->_connect();
		}
	}

	/**
	 * Adds a new query to the query log
	 *
	 * @param array $resultDetails An array of details to save to the log
	 * @return void
	 */
	protected function logQuery( array $resultDetails ) {
		$this->queryLog[] = $resultDetails;
	}

	/**
	 * Returns the query log as an array of arrays
	 *
	 * NB: to use the querylog, setQueryLogging() must be set to true
	 *
	 * @return array
	 */
	public function getQueryLog() {
		return $this->queryLog;
	}

	/**
	 * Clears the query log
	 *
	 * NB: queryCount remains unaffected
	 *
	 * @return void
	 */
	public function clearQueryLog() {
		$this->queryLog = array();
	}

	/**
	 * Returns the number of queries this connection has made.
	 *
	 * @return int
	 */
	public function getQueryCount() {
		return $this->queryCount;
	}


	/**
	 * Perform a crosstab query and correlate the results into an array
	 *
	 * Requires an SQL query that selects three fields - x, y and data.
	 * Executes query, and builds a two-dimensional array in which each
	 * element contains one cell of the data field referenced like this:
	 *
	 * array[y][x] = data
	 *
	 * For argument list, see query().
	 *
	 * @return array A tabulated two-dimensional array of data
	 */
	public function queryCrosstab() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getCrosstab();
	}

	/**
	 * Execute a query and return the contents of row 1 as an associative array
	 *
	 * Intended to be used to retrieve a single row from a query
	 * that will match exactly one row.
	 *
	 * For argument list, see query().
	 *
	 * @return array A row of data as key/value pairs
	 */
	public function queryRow() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getRow();
	}

	/**
	 * Execute a query and return the contents of row 1, column 1
	 *
	 * Intended to be used to retrieve a single value from a query
	 * that will match one row and one field.
	 *
	 * For argument list, see query().
	 *
	 * @return mixed Value from row 1, column 1
	 */
	public function querySingle() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getSingle();
	}

	/**
	 * Execute a query and return all results as a numeric array of rows, each row an associative array
	 *
	 * Entirre resultset is loaded into memory - use only on small resultsets
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function queryAllRows() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getAllRows();
	}

	/**
	 * Execute a query and return all results as an associative array
	 *
	 * Query must return two columns, one called 'k' and another called 'v'.  For argument list, see query().
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function queryLookupTable() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getLookupTable();
	}

	/**
	 * Execute a query and return all results as a single array
	 *
	 * Uses the first column in each row.
	 *
	 * For argument list, see query().
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function queryList() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getList();
	}

	/**
	 * Exectue a query and return results as a CSV
	 *
	 * Returns an entire result set as a single string formatted as a CSV.
	 *
	 * For argument list, see query().
	 *
	 * @return string The results as a CSV
	 */
	public function queryCSV() {
		$results = call_user_func_array(array($this, 'query'), func_get_args());
		return $results->getCSV();
	}


	public static function parseDate($date, DateTimeZone $timezone = NULL) {
		if ($date instanceof DateTime) return $date;
		if (is_object($date) and method_exists($date, '__toString')) $date = (string)$date;
		if (empty($date) or is_object($date)) return null;
		$days = '0?[1-9]|[12][0-9]|3[01]';
		$months = '0?[1-9]|1[0-2]';
		$year = '\d\d|\d\d\d\d';
		$sep = '[\/\-\.\\\,]';

		// If the date is of the format dd/mm/yy or the format dd/mm/yyyy then rearrange into
		// the MySQL format to avoid ambiguity between US and UK date formats
		$date = preg_replace_callback("/\b($days)$sep($months)$sep($year)\b/", function($matches) {
			$month = $matches[2];
			$days = $matches[1];
			$year = $matches[3];

			// Convert 2-digit dates to 4-digit dates, to prevent dd-mm-yy being interpreted by PHP as
			// hh-mm-ss.  Assume the year is the closest option to the current year.
			if (strlen($year) == 2) {
				$curcentury = substr(date("Y"), 0, 2);
				$versions = array();
				for ($i = -1; $i < 2; $i++) {
					$versions[$version = ($curcentury + $i).$year] = abs($version - date("Y"));
				}
				$year = array_search(min($versions), $versions);
			}
			return $year."-".$month."-".$days;
		}, $date);

		// If the date is numeric, assume it is a UNIX timestamp.
		if (is_numeric($date)) $date = "@".$date;

		try {
			$datetime = is_null($timezone) ? new \DateTime($date) : new \DateTime($date, $timezone);
			if ($date[0] != '@') return $datetime;

			// If the input was a UNIX timestamp, set the timezone to stop it defaulting to UTC
			if (is_null($timezone)) $timezone = new \DateTimeZone(date_default_timezone_get());
			$datetime->setTimezone($timezone);
			return $datetime;
		} catch (Exception $e) {
			return false;
		}
	}

}
