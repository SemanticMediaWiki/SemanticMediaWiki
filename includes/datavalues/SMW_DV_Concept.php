<?php
/**
 * @ingroup SMWDataValues
 */

/**
 * This datavalue is used as a container for concept descriptions as used
 * on Concept pages with the #concept parserfunction. It has a somewhat
 * non-standard interface as compared to other datavalues, but this is not
 * an issue.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWConceptValue extends SMWDataValue {

	protected function parseUserValue( $value ) {
		throw new Exception( 'Concepts cannot be initialized from user-provided strings. This should not happen.' );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataItem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {

		if ( $dataItem->getDIType() !== SMWDataItem::TYPE_CONCEPT ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$this->m_caption = $dataItem->getConceptQuery(); // probably useless

		return true;
	}

	protected function clear() {
		$this->m_dataitem = new \SMW\DIConcept( '', '', 0, -1, -1, $this->m_typeid );
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return $this->getShortWikiText( $linker ); // should be save (based on xsdvalue)
	}

	public function getLongWikiText( $linked = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			return $this->m_caption;
		}
	}

	public function getLongHTMLText( $linker = null ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			return $this->m_caption; // should be save (based on xsdvalue)
		}
	}

	public function getWikiValue() {
		/// This should not be used for anything. This class does not support wiki values.
		return str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $this->m_dataitem->getConceptQuery() );
	}

	/// Return the concept's defining text (in SMW query syntax)
	public function getConceptText() {
		return $this->m_dataitem->getConceptQuery();
	}

	/// Return the optional concept documentation.
	public function getDocu() {
		return $this->m_dataitem->getDocumentation();
	}

	/// Return the concept's size (a metric used to estimate computation complexity).
	public function getSize() {
		return $this->m_dataitem->getSize();
	}

	/// Return the concept's depth (a metric used to estimate computation complexity).
	public function getDepth() {
		return $this->m_dataitem->getDepth();
	}

	/// Return the concept's query feature bit field (a metric used to estimate computation complexity).
	public function getQueryFeatures() {
		return $this->m_dataitem->getQueryFeatures();
	}

}
