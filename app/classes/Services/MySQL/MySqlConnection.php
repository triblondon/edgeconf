<?php
/**
 * Class for connection to mysql database
 *
 * @codingstandard ftlabs-phpcs
 * @copyright The Financial Times Limited [All Rights Reserved]
 */

namespace Services\MySQL;

use mysqli;

// COMPLEX:SH:20141002: Note that although importing \Exception
// without an alias does work as desired here, it will conflict
// with any project that makes use of FTLabs\Exception.
use Exception as BaseException;

use DateTime;
use DateTimeZone;

class MySqlConnection {

	protected $server, $username, $password;
	private $dbname;
	private $connectionOpened = false;
	private $conn;
	private $mode, $args;
	protected $reconnectOnFail = true;
	private $reconnectCount = 0;

	protected $logQueries = false;
	protected $queryLog = array();
	protected $suppressErrors = false;
	protected $timezone = null;
	private $queryCount = 0;
	private $deadlockRetry = false;
	private $connectionComment = "";

	const PARAM_SERVER = 1;
	const PARAM_DBNAME = 2;
	const PARAM_USERNAME = 3;
	const PARAM_PASSWORD = 4;

	const CONFIG_FILE_USERNAME = '/etc/sysconfig/mysql-app-user';
	const CONFIG_FILE_PASSWORD = '/etc/sysconfig/mysql-app-password';

	const CR_SERVER_GONE = 2006;
	const CR_SERVER_LOST = 2013;
	const ER_LOCK_WAIT_TIMEOUT = 1205;
	const ER_LOCK_DEADLOCK = 1213;

	const MAX_CONNECTION_FAILURES = 2;
	const MAX_PARSE_FAILURES = 2;

	/**
	 * Creates a new MySqlConnection instance, but dosen't connect until needed.
	 *
	 * This can be instantiated with either one or four arguments. If one argument is
	 * provided, it is assumed to be an associative array with MySqlConnection PARAM_*
	 * constants as keys. If the array is used, the minimum requirement is that the
	 * value for the key PARAM_SERVER is supplied. If values for the keys
	 * PARAM_USERNAME and/or PARAM_PASSWORD are not supplied, then the username and/or
	 * password will be read from the filesystem. This permits the application to use
	 * different credentials per environment, and eliminates the need to hard-code
	 * credentials in applications.
	 *
	 * @param string $server   The server to connect to
	 * @param string $username The username to use for authentication
	 * @param string $password The password to use for authentication
	 * @param string $dbname   The name of the database to connect to
	 * @return void
	 */
	public function __construct() {

		// This constructor supports two formats of argument list:
		switch (func_num_args()) {

			// A single array of key => value options
			case 1:
				$options = func_get_arg(0);
				if (!isset($options[self::PARAM_SERVER])) {
					throw new MySqlConnectionException('MySqlConnection constructor parameter PARAM_SERVER is required');
				} else {
					$this->server = $options[self::PARAM_SERVER];
					$this->username = isset($options[self::PARAM_USERNAME]) ? $options[self::PARAM_USERNAME] : $this->_getConfigVariable(self::CONFIG_FILE_USERNAME);
					$this->password = isset($options[self::PARAM_PASSWORD]) ? $options[self::PARAM_PASSWORD] : $this->_getConfigVariable(self::CONFIG_FILE_PASSWORD);
					$this->dbname = isset($options[self::PARAM_DBNAME]) ? $options[self::PARAM_DBNAME] : '';
				}
				break;

			// Backwards-compatible constructor format
			case 4:
				$this->server = func_get_arg(0);
				$this->username = func_get_arg(1);
				$this->password = func_get_arg(2);
				$this->dbname = func_get_arg(3);
				break;

			default:
				throw new MySqlConnectionException('MySqlConnection constructed with wrong number of arguments');
				break;
		}

		// Other set-up operations
		$this->connectionComment = "CorrelationID:".(isset($_SERVER["HTTP_X_VARNISH"])?$_SERVER["HTTP_X_VARNISH"]:0);
	}

	/**
	 * Attempt to obtain a configuration variable by reading the contents of the specified file
	 *
	 * @param string $filename The file which contains the required configuration variable
	 * @return string
	 */
	private static function _getConfigVariable($filename) {
		$exceptionContext = array(
			'filename' => $filename,
		);
		if (!is_file($filename)) {
			throw new MySqlConnectionException('MySqlConnection config file not found');
		} elseif (!is_readable($filename)) {
			throw new MySqlConnectionException('MySqlConnection config file not readable');
		} elseif (($variable = @file_get_contents($filename)) === false) {
			if (($error = error_get_last()) !== null) {
				$exceptionContext['error'] = $error['message'];
			}
			throw new MySqlConnectionException('Error attempting to read from MySqlConnection config file');
		} else {
			return trim($variable);
		}
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

		$this->connectionOpened = false;

		if (!($this->conn = mysqli_init()) instanceof mysqli) {
			throw new MySqlConnectionException('Unable to create MySQLi instance');

		} else {
			$failedConnectionAttempts = 0;
			do {

				// It is necessary to set the connection timeout and autocommit inside this loop, since MySQLi will reset these variables after a failed connection attempt
				if (!$this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2)) {
					throw new MySqlConnectionException("Error setting MySQL connection timeout: $this->conn->error", $this->conn->errno);

				} elseif (!$this->conn->options(MYSQLI_INIT_COMMAND, 'SET autocommit = 1')) {
					throw new MySqlConnectionException("Error enabling MySQL 'autocommit' system variable: $this->conn->error", $this->conn->errno);

				} elseif (!@$this->conn->real_connect($this->server, $this->username, $this->password, $this->dbname)) {
					if (++$failedConnectionAttempts == self::MAX_CONNECTION_FAILURES) {
						throw new MySqlConnectionException("Connection to database server '{$this->server}' could not be established: {$this->conn->error}", $this->conn->errno);
					} else {
						trigger_error("Database connection error '{$this->conn->error}' server '{$this->server}' (will try again) eh:noreport eh:hashcode=DB".str_pad($this->conn->errno, 6, '0', STR_PAD_LEFT).' eh:tolerance=10/day', E_USER_WARNING);
						sleep($failedConnectionAttempts);
					}
				} else {
					$this->connectionOpened = true;
				}
			} while (!$this->connectionOpened);

			if (!$this->conn->set_charset('utf8')) {
				throw new MySqlConnectionException("Error setting MySQL client character set: $this->conn->error", $this->conn->errno);

			} elseif (!empty($this->timezone) and $this->runQuery('SET time_zone = "' . $this->sqlenc($this->timezone) . '"') === false) {
				throw new MySqlConnectionException("Error setting MySQL session time zone: $this->conn->error", $this->conn->errno);
			}
		}
	}


	/**
	 * This is run on unserialize, at which point there will
	 * be no database connection, and so leaving connectionOpened
	 * set to true will result in errors.
	 *
	 * An example of where this is relevant is during automated
	 * tests of code that assumes a global database object.
	 * Phpunit preserves globals between tests by serializing and
	 * unserializing them.
	 *
	 * @return void
	 */
	public function __wakeup() {
		$this->connectionOpened = false;
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

		// Prepend debugging comment
		if (!empty($this->connectionComment)) {
			$queryExpr = "/* ".str_replace("*/", "* /", $this->sqlenc($this->connectionComment))." */ ".$queryExpr;
		}

		// Run query and return the result
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

		$failedParseAttempts = 0;
		do {
			if (!$this->isConnected()) $this->_connect();
			$args = func_get_args();
			$queryExpr = call_user_func_array(array($this, 'parse'), $args);
			if (empty($queryExpr)) {

				// Do some diagnostices to try and work out why the query is empty
				switch (preg_last_error()) {
					case PREG_NO_ERROR:
						$error = "No PREG error.";
						break;
					case PREG_INTERNAL_ERROR:
						$error = "Internal PREG error.";
						if (++$failedParseAttempts < self::MAX_PARSE_FAILURES) {
							trigger_error("Query is empty: $error eh:caller eh:noreport eh:hashcode=DBE17747 eh:tolerance=5/day", E_USER_WARNING);
							sleep($failedParseAttempts);
							continue 2;
						}
						break;
					case PREG_BACKTRACK_LIMIT_ERROR:
						$error = "Backtrack limit exhasuted.";
						break;
					case PREG_RECURSION_LIMIT_ERROR:
						$error = "Too much recursion.";
						break;
					case PREG_BAD_UTF8_ERROR:
						$error = "Bad UTF8.";
						break;
					case PREG_BAD_UTF8_OFFSET_ERROR:
						$error = "Bad UTF8 offset.";
						break;
					default:
						$error = "Unknown PREG error.";
						break;
				}
				throw new MySqlQueryException("Query is empty: $error eh:caller");
			}
		} while (empty($queryExpr));

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

			if ($this->reconnectCount++ < self::MAX_CONNECTION_FAILURES) {
				sleep(2);
				return call_user_func_array(array($this, "query"), func_get_args());
			} else {
				$this->reconnectCount = 0;
			}
		}
		if (!$resultObject and ($resultDetails['errorNo'] == self::ER_LOCK_WAIT_TIMEOUT)) {

			// For debug, get full processlist
			$proclistres = $this->runQuery("SHOW FULL PROCESSLIST");

			// Made global so it's easier to find in Error Handler
			global $mysqldebug_proclist;

			// If the fetch_all method exists (requires mysqlnd), then use it.
			if (isset($this->result) and method_exists($this->result, 'fetch_all')) {
				$mysqldebug_proclist = $this->result->fetch_all(MYSQLI_ASSOC);
			} else {
				$mysqldebug_proclist = array();
				while ($row = $proclistres->fetch_assoc()) $mysqldebug_proclist[] = $row;
			}
			throw new MySqlLockTimeoutException('MySQL lock wait timeout eh:caller');
		}
		if (!$resultObject and $resultDetails['errorNo'] == self::ER_LOCK_DEADLOCK) {

			// If deadlock found, retry once.
			if (!$this->deadlockRetry) {
				$this->deadlockRetry = true;
				sleep(5);
				return call_user_func_array(array($this, "query"), func_get_args());
			}
		}
		if (!$resultObject and !$this->suppressErrors) {
			$this->runQuery("ROLLBACK");
			throw new MySqlQueryException($resultDetails['errorMsg']." (".$resultDetails['errorNo'].") eh:caller occured in query: ".$queryExpr, $resultDetails['errorNo']);
		}

		// If the result worked, reset the deadlockretry variable
		if ($resultObject) $this->deadlockRetry = false;
		return new MySqlResult($resultObject, $resultDetails, $this->timezone);
	}




	/**
	 * Construct a complete query from a prepared statement using either form of prepared statement.
	 *
	 * For argument list, see query().
	 *
	 * @return string Complete query
	 */
	public function parse() {
		if (!func_num_args()) {
			throw new MySqlQueryException('No query specified. eh:caller');
		}
		$this->args = func_get_args();
		$queryExpr = array_shift($this->args);

		if ((strpos($queryExpr, "'") !== false or strpos($queryExpr, "\"") !== false)) trigger_error("Literal strings should not be included in queries.  Use a prepared statement. eh:caller", E_USER_DEPRECATED);

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
			if (!count($this->args)) {
				throw new MySqlQueryException('Not enough parameters. eh:caller');
			}
			$value = array_shift($this->args);
			$sprintfFormat = trim($match[1], '%');
			$key = null;
		} else {
			$key = $match[1];

			// Use array_key_exists instead of isset because isset returns false if value is null
			if (!array_key_exists($key, $this->args)) {
				throw new MySqlQueryException("Can't find argument for '$key'. eh:caller");
			}
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
					$value = self::_convertHumanTime($value);
					$beenEncoded = false;
					if (!$value) {
						$value = null;
						break;
					}
					if ($modifier == 'utcdate') {
						$value->setTimezone(new DateTimeZone("UTC"));
					} elseif ($this->timezone) {
						$value->setTimezone(new DateTimeZone($this->timezone));
					}
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

		if (!is_null($value) && !is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
			throw new MySqlQueryException('Can\'t convert value to string eh:caller');
		}

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
	protected function sqlenc($val) {
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
	public function setQueryLogging($newval) {
		$this->logQueries = (bool)$newval;
	}


	/**
	 * Set a custom debugging comment to prepend to queries, overriding the default
	 *
	 * @param string $comment the new comment
	 * @return void
	 */
	public function setConnectionComment($comment) {
		$this->connectionComment = $comment;
	}

	/**
	 * Sets the timezone that should be used for the connection
	 *
	 * @param string $tz TZ-format timezone name
	 * @return void
	 */
	public function setTimezone($tz) {
		$this->timezone = $tz;
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
			if (!$this->conn->select_db($dbname)) {
				throw new MySqlConnectionException('Database was not found eh:hashcode=74154CDD');
			}
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

	/**
	 * Converts a date into a DateTime object
	 *
	 * @param mixed        $date     A DateTime object, unixtimestamp or string representation of a date
	 * @param DateTimeZone $timezone The timezone the date is in. (defaults to current timezone).  NB: this is ignored if $date is a UNIX timestamp or specifies a timezone.
	 * @return DateTime or false on failure.
	 */
	private static function _convertHumanTime($date, DateTimeZone $timezone = NULL) {
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
			$datetime = is_null($timezone) ? new DateTime($date) : new DateTime($date, $timezone);
			if ($date[0] != '@') return $datetime;

			// If the input was a UNIX timestamp, set the timezone to stop it defaulting to UTC
			if (is_null($timezone)) $timezone = new DateTimeZone(date_default_timezone_get());
			$datetime->setTimezone($timezone);
			return $datetime;
		} catch (BaseException $e) {
			return false;
		}
	}
}
