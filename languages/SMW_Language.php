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
	protected $smwSpecialProperties;
	protected $m_DatatypeLabels;
	protected $m_DatatypeAliases = array();
	protected $m_Namespaces;
	protected $m_NamespaceAliases = array();
	

	/**
	 * Function that returns an array of namespace identifiers.
	 */
	function getNamespaces() {
		return $this->m_Namespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any.
	 */
	function getNamespaceAliases() {
		return $this->m_NamespaceAliases;
	}

	/**
	 * Return all labels that are available as names for built-in datatypes. Those
	 * are the types that users can access via [[has type::...]] (more built-in 
	 * types may exist for internal purposes but the user won't need to
	 * know this). The returned array is indexed by (internal) type ids.
	 */
	function getDatatypeLabels() {
		return $this->m_DatatypeLabels;
	}

	/**
	 * Return an array that maps aliases to internal type ids. All ids used here
	 * should also have a primary label defined in m_DatatypeLabels.
	 */
	function getDatatypeAliases() {
		return $this->m_DatatypeAliases;
	}

	/**
	 * Function that returns the labels for the special relations and attributes.
	 */
	function getSpecialPropertiesArray() {
		return $this->smwSpecialProperties;
	}

	/**
	 * Extends the array of special properties with a mapping from an $id to a
	 * language dependent label.
	 * NOTE: this function is provided for ad hoc compatibility with the Halo project.
	 * A better solution will replace it at some time.
	 */
	function addSpecialProperty($id, $label) {
		if (array_key_exists($id, $this->smwSpecialProperties)) {
			trigger_error('The ID "' . $id . '" already belongs to the special property "' . $this->smwSpecialProperties[$key] . '" and thus cannot be used for "' . $label . '".', E_USER_WARNING);
		} elseif ($id < 1000) {
			trigger_error('IDs below 1000 are not allowed for custom special properties. Registration of "' . $label . '" failed.', E_USER_WARNING);
		} else {
			$this->smwSpecialProperties[$id] = $label;
		}
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


