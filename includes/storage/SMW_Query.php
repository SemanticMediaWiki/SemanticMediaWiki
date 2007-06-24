<?php
/**
 * This file contains the class for representing queries in SMW, each
 * consisting of a query description and possible query parameters.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once($smwgIP . '/includes/storage/SMW_Description.php');

/**
 * Representation of queries in SMW, each consisting of a query 
 * description and various parameters. Some settings might also lead to 
 * changes in the query description.
 *
 * Most additional query parameters (limit, sort, ascending, ...) are 
 * interpreted as in SMWRequestOptions (though the latter contains some
 * additional settings).
 */
class SMWQuery {

	const MODE_INSTANCES = 1;
	const MODE_COUNT = 2;
	const MODE_DEBUG = 3;

	public $limit = -1;
	public $offset = 0;
	public $sort = false;
	public $ascending = true;
	public $sortkey = false;
	public $querymode = SMWQuery::MODE_INSTANCES;

	protected $m_description;
	protected $m_errors; // keep any errors that occurred so far
	protected $m_querystring = false; // string (inline query) version (if fixed and known)

	public function SMWQuery($description = NULL) {
		$this->m_description = $description;
		$this->m_errors = array();
	}

	public function setDescription(SMWDescription $description) {
		$this->m_description = $description;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getErrors() {
		return $this->m_errors;
	}

	public function addErrors($errors) {
		$this->m_errors = array_merge($this->m_errors, $errors);
	}

	public function setQueryString($querystring) {
		$this->m_querystring = $querystring;
	}

	public function getQueryString() {
		if ($this->m_querystring !== false) {
			return $this->m_querystring;
		} elseif ($this->m_description !== NULL) {
			return $this->m_description->getQueryString();
		} else {
			return '';
		}
	}

}

?>