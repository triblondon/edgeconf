<?php
/**
 * Class for results returned by MySqlConnection
 *
 * @codingstandard ftlabs-phpcs
 * @copyright The Financial Times Limited [All Rights Reserved]
 */

namespace Services\MySQL;

use mysqli_result;
use Iterator;
use Countable;

class MySqlResult implements Iterator, Countable {

	protected $result, $affectedRows, $insertId, $errorNo, $queryExpr, $errorMsg, $objectClassName, $objectParams, $current, $currentKey, $numRows, $timeTaken, $dateExecuted, $timezone;


	public function __construct($resultObject, array $resultDetails, $timezone='UTC') {
		$this->result = $resultObject;
		$this->affectedRows = (int)$resultDetails['affectedRows'];
		$this->insertId = (int)$resultDetails['insertId'];
		$this->errorNo = (int)$resultDetails['errorNo'];
		$this->errorMsg = (string)$resultDetails['errorMsg'];
		$this->queryExpr = (string)$resultDetails['queryExpr'];
		if ($timezone == '+00:00') $timezone = 'UTC';
		$this->timezone = $timezone;

		if (isset($resultDetails["timeTaken"])) {
			$this->timeTaken = $resultDetails["timeTaken"];
		}

		if (isset($resultDetails["dateExecuted"])) {
			$this->dateExecuted = $resultDetails["dateExecuted"];
		}

		$this->currentKey = -1;
		$this->current = false;
	}


	/* Impelement the Iterator Interface */

	/**
	 * Returns the current result row
	 *
	 * @return mixed
	 */
	public function current () {
		return $this->current;
	}

	/**
	 * Returns the current key
	 *
	 * @return int
	 */
	public function key () {
		return $this->currentKey;
	}

	/**
	 * Moves forward to the next result row
	 *
	 * @return void
	 */
	public function next () {
		if (!($this->result instanceof mysqli_result) ) return;
		if (isset($this->objectClassName)) {
			if ($this->objectParams) $this->current = $this->result->fetch_object($this->objectClassName, $this->objectParams);
			else $this->current = $this->result->fetch_object($this->objectClassName);
		}
		else $this->current = $this->convertDateTimes($this->result->fetch_assoc());
		$this->currentKey++;
	}

	/**
	 * Rewinds to the first result row
	 *
	 * @return void
	 */
	public function rewind () {
		if (!count($this)) return;
		$this->result->data_seek(0);
		$this->currentKey = -1;
		$this->next();
	}


	/* Implement the Countable Interface */

	/**
	 * Returns whether or not there is a current result row
	 *
	 * @return void
	 */
	public function valid () {
		return (bool)$this->current;
	}

	/**
	 * Returns the number of results returned by the query
	 *
	 * @return int
	 */
	public function count () {
		if (isset($this->numRows)) return $this->numRows;
		if (!($this->result instanceof mysqli_result)) return 0;
		return $this->numRows = $this->result->num_rows;
	}

	/**
	 * Get the number of rows affected by the query
	 *
	 * @return int
	 */
	public function getAffectedRows() {
		return $this->affectedRows;
	}

	/**
	 * Get the ID generated in the query
	 *
	 * @return int
	 */
	public function getInsertId() {
		return $this->insertId;
	}

	/**
	 * Returns the numerical value of the error message from the query
	 *
	 * @return int
	 */
	public function getErrorNo() {
		return $this->errorNo;
	}

	/**
	 * Returns the text of the error message from the query
	 *
	 * @return string
	 */
	public function getError() {
		return $this->errorMsg;
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
	public function getCrosstab() {
		$data = array();
		foreach ($this as $row) {
			if (!isset($row['x']) || !isset($row['y']) || !isset($row['data'])) {
				throw new MySqlQueryException('x, y and data are not all set for crosstab', get_defined_vars());
			}
			$data[$row["y"]][$row["x"]] = $row["data"];
		}
		return $data;
	}

	/**
	 * Fetch the next row from the result set as an associative array
	 *
	 * @return array A row of data as key/value pairs
	 */
	public function getRow() {
		$this->next();
		if (!$this->valid()) return null;
		return (array)$this->current();
	}

	/**
	 * Fetch a single 'cell' of data from the first row returned from a query.  COMPLEX: This should only be called by the connection object as you can't guarantee that this returns the first result if used directly.  As it does not rewind.  Perhaps call $this->result->data_seek(0); (Unknown performance problems?)
	 *
	 * @param integer $columnoffset Index of column to fetch, where 0 is the leftmost column. Optional, defaults to 0.
	 * @return array A row of data as key/value pairs
	 */
	public function getSingle($columnoffset = 0) {
		if (!count($this)) return null;
		$row = $this->convertDateTimes($this->result->fetch_array(MYSQLI_NUM));
		return $row[$columnoffset];
	}

	/**
	 * Return all results as a numeric array of rows, each row an associative array
	 *
	 * Entirre resultset is loaded into memory - use only on small resultsets
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function getAllRows() {
		$data = array();
		foreach ($this as $row) {
			$data[] = $this->convertDateTimes($row);
		}
		return $data;
	}

	/**
	 * Return all results as an associative array
	 *
	 * Query must return two columns, one called 'k' and another called 'v'.
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function getLookupTable() {
		$data = array();
		foreach ($this as $row) {
			if (!array_key_exists('k', $row) or !array_key_exists('v', $row)) {
				throw new MySqlQueryException('k and v are not all set for lookup table', get_defined_vars());
			}
			$data[$row["k"]] = $row["v"];
		}
		return $data;
	}

	/**
	 * Execute a query and return all results as a single array
	 *
	 * Uses the first column of each row.
	 *
	 * @return array Array containing one element per row in the resultset
	 */
	public function getList() {
		$data = array();
		foreach ($this as $row) $data[] = current($row);
		return $data;
	}

	/**
	 * Return a result set as a CSV
	 *
	 * Returns an entire result set as a single string formatted as a CSV.  All
	 * parameters are optional.
	 *
	 * @param string $lineend Row separator (default is a newline)
	 * @param string $delim   Field separator (default is a comma)
	 * @param string $escape  String to use to prefix to escape the $enclose string if it appears in the data (default is a backslash)
	 * @param string $enclose String to insert before and after each 'cell' (default is a double-quote)
	 * @return integer The results as a CSV
	 */
	public function getCSV($lineend="\n", $delim=",", $escape="\\", $enclose="\"") {
		$op = '';
		foreach ($this as $row) {
			if (!$op) {
				$keys = array_keys($row);
				if ($enclose) foreach ($keys as $colkey=>$col) $keys[$colkey] = $enclose.str_replace($enclose, $escape.$enclose, $col).$enclose;
				$op = join($delim, $keys).$lineend;
			}
			if ($enclose) foreach ($row as $colkey=>$col) $row[$colkey] = $enclose.str_replace($enclose, $escape.$enclose, $col).$enclose;
			$op .= join($delim, $row).$lineend;
		}
		return $op;
	}

	/**
	 * Makes the class return result rows as objects rather than arrays
	 *
	 * @param string $className The name of the class to instantiate, set the properties of and return. If not specified, a stdClass object is returned.
	 * @param array  $params    An optional array of parameters to pass to the constructor for class_name objects.
	 * @return void
	 */
	public function setReturnObject($className = 'stdClass', array $params = null) {
		$this->objectClassName = $className;
		$this->objectParams = $params;
		$this->rewind();
	}

	private function convertDateTimes($row) {
		if ($row) {
			$zone = new \DateTimeZone($this->timezone);
			foreach ($row as $key=>$val) {
				if (is_string($val) and preg_match("/^\d+\-\d+\-\d+ \d+:\d+:\d+$/", $val)) {
					$row[$key] = new \DateTime($val, $zone);
				}
			}
		}
		return $row;
	}

}
