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
			$firstPropertyIsPageDef = false; // we onlt drop first property if its of type wpg
			$count = 0;
			// generate property section
			foreach ( $res->getPrintRequests() as $pr ) {
				if ( array_key_exists( $pr->getTypeID(), $this->types ) ) {
					$propertystack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '" : { "valueType": "' . $this->types[$pr->getTypeID()] . '" }';
				} else {
					$propertystack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '" : { "valueType": "text" }';
				}
				// if we're at the first item and it doesn't have a label its the page
				if ( $count == 0 && $pr->getLabel() == "" ) {
					$firstPropertyIsPageDef = true;
				}
				$count++;
			}
			// drop bogus empty first item that got stuffed in here (I don't know why its here)
			if ( $firstPropertyIsPageDef ) {
				array_shift( $propertystack ); // drop first property
			}

			$properties = "\"properties\": {\n\t\t" . implode( ",\n\t\t", $propertystack ) . "\n\t}";

			// generate items section
			$row = $res->getNext();
			while ( $row !== false ) {
				$valuestack = array(); // contains Property-Value pairs to characterize an Item
				$count = 0; // counter
				$prefixedtext = ''; // save label for uri specification
				foreach ( $row as $field ) {
					$req = $field->getPrintRequest();
					// when we're processing the first item and its a page we process it a little differently
					// this is the original code
					if ( $count == 0 && $firstPropertyIsPageDef ) {
						$values = '';
						foreach ( $field->getContent() as $value ) {
							$values = $value->getShortText( $outputmode, null ); // assign last value to label
							$prefixedtext = $value->getPrefixedText();
						}
						$valuestack[] = '"label": "' . $values . '"';
						$label = $values;
					} else {
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
						if ( $finalvalues != '' ) $valuestack[] = '"' . str_replace( " ", "_", strtolower( $req->getLabel() ) ) . '": ' . $finalvalues . '';
					}
					$count++;
				}
				// if we had a page definition, stuff in the uri
				if ( $firstPropertyIsPageDef ) {
					$valuestack[] = '"uri" : "' . $wgServer . $wgScriptPath . '/index.php?title=' . $prefixedtext . '"';
				}

				// try to determine type/category
				$catlist = array();
				$dbr = wfGetDB( DB_SLAVE );
				$cl  = $dbr->tableName( 'categorylinks' );
				$arttitle = Title::newFromText( $label );
				if ( $arttitle instanceof Title ) {
					$catid = $arttitle->getArticleID();
					$catres  = $dbr->select( $cl, 'cl_to', "cl_from = $catid", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
					while ( $catrow = $dbr->fetchRow( $catres ) ) $catlist[] = $catrow[0];
					$dbr->freeResult( $catres );
					if ( sizeof( $catlist ) > 0 ) $valuestack[] = '"type" : "' . $catlist[0] . '"';
				}
				// create property list of item
				$itemstack[] = "\t{\n\t\t\t" . implode( ",\n\t\t\t", $valuestack ) . "\n\t\t}";
				$row = $res->getNext();
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



