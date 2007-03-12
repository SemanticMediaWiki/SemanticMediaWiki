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
 */
class SMWQuery {
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