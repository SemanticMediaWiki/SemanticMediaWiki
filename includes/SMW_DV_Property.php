<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Objects of this class represent properties in SMW.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWPropertyValue extends SMWWikiPageValue {
	
	public function __construct($typeid) {
		parent::__construct($typeid);
		$this->m_fixNamespace = SMW_NS_PROPERTY;
	}
}
