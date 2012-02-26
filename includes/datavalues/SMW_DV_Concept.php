<?php
/**
 * @file
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
		throw new Exception( 'Concepts cannot be initialised from user-provided strings. This should not happen.' );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_CONCEPT ) {
			$this->m_dataitem = $dataItem;
			$this->m_caption = $dataItem->getConceptQuery(); // probably useless
			return true;
		} else {
			return false;
		}
	}

	protected function clear() {
		$this->m_dataitem = new SMWDIConcept( '', '', 0, -1, -1, $this->m_typeid );
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

	/**
	 * Function not called anywhere but code kept for reference before
	 * migrating it to SMWExporter.
	 */
	private function getExportData() {
		if ( $this->isValid() ) {
			$qp = new SMWQueryParser();
			$desc = $qp->getQueryDescription( str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $this->m_dataitem->getConceptQuery() ) );
			$exact = true;
			$owldesc = $this->descriptionToExpData( $desc, $exact );
			if ( !$exact ) {
				$result = new SMWExpData( new SMWExpResource( '' ) );
				$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ),
				                                new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Class' ) ) );
				$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'subClassOf' ), $owldesc );
				return $result;
			} else {
				return $owldesc;
			}
		} else {
			return null;
		}
	}

	public function descriptionToExpData( $desc, &$exact ) {
		if ( ( $desc instanceof SMWConjunction ) || ( $desc instanceof SMWDisjunction ) ) {
			$result = new SMWExpData( new SMWExpResource( '' ) );
			$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ),
			                                new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Class' ) ) );
			$elements = array();
			foreach ( $desc->getDescriptions() as $subdesc ) {
				$element = $this->descriptionToExpData( $subdesc, $exact );
				if ( $element === false ) {
					$element = new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Thing' ) );
				}
				$elements[] = $element;
			}
			$prop = ( $desc instanceof SMWConjunction ) ? 'intersectionOf':'unionOf';
			$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', $prop ),
			                                SMWExpData::makeCollection( $elements ) );
		} elseif ( $desc instanceof SMWClassDescription ) {
			if ( count( $desc->getCategories() ) == 1 ) { // single category
				$result = new SMWExpData( SMWExporter::getResourceElement( end( $desc->getCategories() ) ) );
			} else { // disjunction of categories
				$result = new SMWExpData( new SMWExpResource( '' ) );
				$elements = array();
				foreach ( $desc->getCategories() as $cat ) {
					$elements[] = new SMWExpData( SMWExporter::getResourceElement( $cat ) ); ;
				}
				$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', 'unionOf' ),
				                                SMWExpData::makeCollection( $elements ) );
			}
			$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ),
			                                new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Class' ) ) );
		} elseif ( $desc instanceof SMWConceptDescription ) {
			$result = new SMWExpData( SMWExporter::getResourceElement( $desc->getConcept() ) );
		} elseif ( $desc instanceof SMWSomeProperty ) {
			$result = new SMWExpData( new SMWExpResource( '' ) );
			$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ),
			                                new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Restriction' ) ) );
			$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', 'onProperty' ),
			                                new SMWExpData( SMWExporter::getResourceElement( $desc->getProperty() ) ) );
			$subdata = $this->descriptionToExpData( $desc->getDescription(), $exact );
			if ( ( $desc->getDescription() instanceof SMWValueDescription ) &&
			     ( $desc->getDescription()->getComparator() == SMW_CMP_EQ ) ) {
				$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', 'hasValue' ), $subdata );
			} else {
				if ( $subdata === false ) {
					$owltype = SMWExporter::getOWLPropertyType( $desc->getProperty()->getPropertyTypeID() );
					if ( $owltype == 'ObjectProperty' ) {
						$subdata = new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Thing' ) );
					} elseif ( $owltype == 'DatatypeProperty' ) {
						$subdata = new SMWExpData( SMWExporter::getSpecialNsResource( 'rdfs', 'Literal' ) );
					} else { // no restrictions at all with annotation properties ...
						return new SMWExpData( SMWExporter::getSpecialNsResource( 'owl', 'Thing' ) );
					}
				}
				$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', 'someValuesFrom' ), $subdata );
			}
		} elseif ( $desc instanceof SMWValueDescription ) {
			if ( $desc->getComparator() == SMW_CMP_EQ ) {
				$result = SMWExporter::getDataItemExpElement( $desc->getDataItem() );
			} else { // alas, OWL cannot represent <= and >= ...
				$exact = false;
				$result = false;
			}
		} elseif ( $desc instanceof SMWThingDescription ) {
			$result = false;
		} else {
			$result = false;
			$exact = false;
		}
		return $result;
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
