<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue is similiar to SMWWikiPageValue in that it represents pages
 * in the wiki. However, it is tailored for uses where it is enough to store
 * the title string of the page without namespace, interwiki prefix, or
 * sortkey. This is useful for "special" properties like "Has type" where the
 * namespace is fixed, and which do not need any of the other settings. The
 * advantage of the reduction of data is that these important values can be
 * stored in smaller tables that allow for faster direct access than general
 * page type values.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWSimpleWikiPageValue extends SMWWikiPageValue {

	protected function parseDBkeys( $args ) {
		$this->m_dbkeyform = $args[0];
		$this->m_namespace = $this->m_fixNamespace;
		$this->m_interwiki = '';
		$this->m_sortkey   = $this->m_dbkeyform;
		$this->m_textform = str_replace( '_', ' ', $this->m_dbkeyform );
		$this->m_id = false;
		$this->m_title = null;
		$this->m_prefixedtext = false;
		$this->m_caption = false;
	}

	public function getDBkeys() {
		$this->unstub();
		return array( $this->m_dbkeyform );
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 1;
	}

	public function getLabelIndex() {
		return 1;
	}

}