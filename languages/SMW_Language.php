<?php
/**
 * @author Markus Krötzsch
 */

/**
 * Base class for all language classes.
 */
abstract class SMW_Language {

	// the message arrays ...
	protected $smwContentMessages;
	protected $smwUserMessages;
	protected $smwDatatypeLabels;
	protected $smwSpecialProperties;

	/**
	 * Function that returns an array of namespace identifiers.
	 */
	abstract function getNamespaceArray();

	/**
	 * Function that returns the localised label for a datatype.
	 */
	function getDatatypeLabel($msgid) {
		return $this->smwDatatypeLabels[$msgid];
	}

	/**
	 * Find the internal message id of some localised message string
	 * for a datatype. If no type of the given name exists (maybe a 
	 * custom of compound type) then FALSE is returned.
	 */
	function findDatatypeMsgID($label) {
		return array_search($label, $this->smwDatatypeLabels);
	}

	/**
	 * Function that returns the labels for the special relations and attributes.
	 */
	function getSpecialPropertiesArray() {
		return $this->smwSpecialProperties;
	}

	/**
	 * Function that returns all content messages (those that are stored
	 * in some article, and can thus not be translated to individual users).
	 */
	function getContentMsgArray() {
		return $this->smwContentMessages;
	}

	/**
	 * Function that returns all user messages (those that are given only to
	 * the current user, and can thus be given in the individual user language).
	 */
	function getUserMsgArray() {
		return $this->smwUserMessages;
	}
}


