<?php
/**
 * Print links to JSON files representing query results.
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer for creating a link to JSON files.
 *
 * @author Fabian Howahl
 * @ingroup SMWQuery
 */
class SMWJSONResultPrinter extends SMWResultPrinter {
	protected $types = array( "_wpg" => "text", "_num" => "number", "_dat" => "date", "_geo" => "text", "_str" => "text" );

	public function getMimeType( $res ) {
		return 'application/JSON';
	}

	public function getFileName( $res ) {
		if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) != '' ) {
			return str_replace( ' ', '_', $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) . '.json';
		} else {
			return 'result.json';
		}
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES:SMWQuery::MODE_NONE;
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_json' );
	}

	protected function getResultText( $res, $outputmode ) {
		global $smwgIQRunningNumber, $wgSitename, $wgServer, $wgScriptPath;
		$result = '';
		if ( $outputmode == SMW_OUTPUT_FILE ) { // create detached JSON file
			$itemstack = array(); // contains Items for the items section
			$propertystack = array(); // contains Properties for the property section

			// generate property section
			foreach ( $res->getPrintRequests() as $pr ) {
				if ( $pr->getMode() != SMWPrintRequest::PRINT_THIS ) {
					if ( array_key_exists( $pr->getTypeID(), $this->types ) ) {
						$propertystack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '" : { "valueType": "' . $this->types[$pr->getTypeID()] . '" }';
					} else {
						$propertystack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '" : { "valueType": "text" }';
					}
				}
			}
			$properties = "\"properties\": {\n\t\t" . implode( ",\n\t\t", $propertystack ) . "\n\t}";

			// generate items section
			while ( ( $row = $res->getNext() ) !== false ) {
				$rowsubject = false; // the wiki page value that this row is about
				$valuestack = array(); // contains Property-Value pairs to characterize an Item
				foreach ( $row as $field ) {
					$pr = $field->getPrintRequest();
					if ( $rowsubject === false ) {
						$rowsubject = $field->getResultSubject();
						$valuestack[] = '"label": "' . $rowsubject->getShortText( $outputmode, null ) . '"';
					}
					if ( $pr->getMode() != SMWPrintRequest::PRINT_THIS ) {
						$values = array();
						$finalvalues = '';
						while ( ( $value = $field->getNextObject() ) !== false ) {
							$finalvalues = '';
							switch ( $value->getTypeID() ) {
								case '_geo':
									$values[] = "\"" . $value->getWikiValue() . "\"";
									break;
								case '_num':
									$values[] = "\"" . $value->getValueKey() . "\"";
									break;
								case '_dat':
									$values[] = "\"" . $value->getYear() . "-" . str_pad( $value->getMonth(), 2, '0', STR_PAD_LEFT ) . "-" . str_pad( $value->getDay(), 2, '0', STR_PAD_LEFT ) . " " . $value->getTimeString() . "\"";
									break;
								default:
									$values[] = "\"" . $value->getShortText( $outputmode, null ) . "\"";
							}

							if ( sizeof( $values ) > 1 ) {
								$finalvalues = "[" . implode( ",", $values ) . "]";
							} else {
								$finalvalues = $values[0];
							}
						}
						if ( $finalvalues != '' ) $valuestack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '": ' . $finalvalues . '';
					}
				}
				if ( $rowsubject !== false ) { // stuff in the page URI and some category data
					$valuestack[] = '"uri" : "' . $wgServer . $wgScriptPath . '/index.php?title=' . $rowsubject->getPrefixedText() . '"';
					$page_cats = smwfGetStore()->getPropertyValues( $rowsubject, SMWPropertyValue::makeProperty( '_INST' ) ); // TODO: set limit to 1 here
					if ( count( $page_cats ) > 0 ) {
						$valuestack[] = '"type" : "' . reset($page_cats)->getShortHTMLText() . '"';
					}
				}

				// create property list of item
				$itemstack[] = "\t{\n\t\t\t" . implode( ",\n\t\t\t", $valuestack ) . "\n\t\t}";
			}

			$items = "\"items\": [\n\t" . implode( ",\n\t", $itemstack ) . "\n\t]";

			// check whether a callback function is required
			if ( array_key_exists( 'callback', $this->m_params ) ) {
				$result = htmlspecialchars( $this->m_params['callback'] ) . "({\n\t" . $properties . ",\n\t" . $items . "\n})";
			} else {
				$result = "{\n\t" . $properties . ",\n\t" . $items . "\n}";
			}

		} else { // just create a link that points to the JSON file
			if ( $this->getSearchLabel( $outputmode ) ) {
				$label = $this->getSearchLabel( $outputmode );
			} else {
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$label = wfMsgForContent( 'smw_json_link' );
			}
			$link = $res->getQueryLink( $label );
			if ( array_key_exists( 'callback', $this->m_params ) ) {
				$link->setParameter( htmlspecialchars( $this->m_params['callback'] ), 'callback' );
			}
			if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) != '' ) { // used as a file name
				$link->setParameter( $this->getSearchLabel( SMW_OUTPUT_WIKI ), 'searchlabel' );
			}
			if ( array_key_exists( 'limit', $this->m_params ) ) {
				$link->setParameter( htmlspecialchars( $this->m_params['limit'] ), 'limit' );
			}
			$link->setParameter( 'json', 'format' );
			$result = $link->getText( $outputmode, $this->mLinker );
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
		}

		return $result;
	}

	public function getParameters() {
		return parent::exportFormatParameters();
	}

}



