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
	
	protected $types = array( '_wpg' => 'text', '_num' => 'number', '_dat' => 'date', '_geo' => 'text', '_str' => 'text' );

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
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_json' );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $wgServer, $wgScriptPath;
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
			while ( ( /* array of SMWResultArray */ $row = $res->getNext() ) !== false ) {
				$rowsubject = false; // the wiki page value that this row is about
				$valuestack = array(); // contains Property-Value pairs to characterize an Item
				$addedLabel = false;
				
				foreach ( $row as /* SMWResultArray */ $field ) {
					$pr = $field->getPrintRequest();
					
					if ( $rowsubject === false && !$addedLabel ) {
						$valuestack[] = '"label": "' . $field->getResultSubject()->getTitle()->getFullText() . '"';
						$addedLabel = true;
					}
					
					if ( $pr->getMode() != SMWPrintRequest::PRINT_THIS ) {
						$values = array();
						$jsonObject = array();
						
						while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
							switch ( $dataValue->getTypeID() ) {
								case '_geo':
									$jsonObject[] = $dataValue->getDataItem()->getCoordinateSet();
									$values[] = FormatJson::encode( $dataValue->getDataItem()->getCoordinateSet() );
									break;
								case '_num':
									$jsonObject[] = $dataValue->getDataItem()->getNumber();
									break;
								case '_dat':
									$jsonObject[] = 
										$dataValue->getYear() . '-' .
										str_pad( $dataValue->getMonth(), 2, '0', STR_PAD_LEFT ) . '-' .
										str_pad( $dataValue->getDay(), 2, '0', STR_PAD_LEFT ) . ' ' .
										$dataValue->getTimeString();
									break;
								default:
									$jsonObject[] = $dataValue->getShortText( $outputmode, null );
							}
						}
						
						if ( !is_array( $jsonObject ) || count( $jsonObject ) > 0 ) {
							$valuestack[] =
								'"' . str_replace( ' ', '_', strtolower( $pr->getLabel() ) ) 
								. '": ' . FormatJson::encode( $jsonObject ) . '';
						}
					}
				}
				
				if ( $rowsubject !== false ) { // stuff in the page URI and some category data
					$valuestack[] = '"uri" : "' . $wgServer . $wgScriptPath . '/index.php?title=' . $rowsubject->getPrefixedText() . '"';
					$page_cats = smwfGetStore()->getPropertyValues( $rowsubject, new SMWDIProperty( '_INST' ) ); // TODO: set limit to 1 here
					
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
			
			// yes, our code can be viewed as HTML if requested, no more parsing needed
			$this->isHTML = $outputmode == SMW_OUTPUT_HTML;
		}

		return $result;
	}

	public function getParameters() {
		return array_merge( parent::getParameters(), $this->exportFormatParameters() );
	}

}
