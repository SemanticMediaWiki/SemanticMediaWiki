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

	public function SMWQuery($description = NULL) {
		$this->m_description = $description;
	}

	public function setDescription(SMWDescription $description) {
		$this->m_description = $description;
	}

	public function getDescription() {
		return $this->m_description;
	}
}

?>