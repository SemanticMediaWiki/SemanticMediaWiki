<?php

/**
 * Print links to JSON files representing query results.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:JSON_format
 * @since 1.5.3
 *
 * @file SWM_QP_JSONlink.php
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Fabian Howahl
 */
class SMWJSONResultPrinter extends SMWExportPrinter {

	/**
	 * Returns human readable label for this printer
	 *
	 * @return string
	 */
	public function getName() {
		return wfMessage( 'smw_printername_json' )->text();
	}

	/**
	 * @see SMWIExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string
	 */
	public function getMimeType( SMWQueryResult $queryResult ) {
		return 'application/JSON';
	}

	/**
	 * @see SMWIExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string|boolean
	 */
	public function getFileName( SMWQueryResult $queryResult ) {
		if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) !== '' ) {
			return str_replace( ' ', '_', $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) . '.json';
		} else {
			return 'result.json';
		}
	}

	/**
	 * File exports use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 *
	 * @param $context
	 *
	 * @return integer
	 */
	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	/**
	 * Returns a filename that is to be sent to the caller
	 *
	 * @param SMWQueryResult $res
	 * @param $outputmode integer
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $res, $outputmode ) {

		if ( $outputmode == SMW_OUTPUT_FILE ) {

			// No results, just bailout
			if ( $res->getCount() == 0 ){
				return $this->params['default'] !== '' ? $this->params['default'] : '';
			}

			// JSON instance
			$json = new SMWJSON( $res );

			// JSON export type
			if ( $this->params['syntax'] === 'obsolete' ) {
				$result = $this->getObsoleteJSON( $res , $outputmode );
			} elseif ( $this->params['syntax'] === 'basic' ) {
				$result = $json->getEncoding( $this->params['syntax'] , $this->params['prettyprint'] );
			} else {
				$result = $json->getEncoding( $this->params['syntax'] , $this->params['prettyprint'] );
			}

		} else {
			// Create a link that points to the JSON file
			$result = $this->getLink( $res, $outputmode )->getText( $outputmode, $this->mLinker );

			// Code can be viewed as HTML if requested, no more parsing needed
			$this->isHTML = $outputmode == SMW_OUTPUT_HTML;
		}

		return $result;
	}

	/**
	 * Compatibility layer for obsolete JSON format
	 *
	 * @since 1.8
	 * @deprecated This method will be removed in 1.10
	 *
	 * @param SMWQueryResult $res
	 * @param $outputmode integer
	 *
	 * @return string
	 */
	private function getObsoleteJSON( SMWQueryResult $res, $outputmode ){
		wfDeprecated( __METHOD__, '1.8' );

		$types = array( '_wpg' => 'text', '_num' => 'number', '_dat' => 'date', '_geo' => 'text', '_str' => 'text' );

		$itemstack = array(); // contains Items for the items section
		$propertystack = array(); // contains Properties for the property section

		// generate property section
		foreach ( $res->getPrintRequests() as $pr ) {
			if ( $pr->getMode() != SMWPrintRequest::PRINT_THIS ) {
				if ( array_key_exists( $pr->getTypeID(), $types ) ) {
					$propertystack[] = '"' . str_replace( " ", "_", strtolower( $pr->getLabel() ) ) . '" : { "valueType": "' . $types[$pr->getTypeID()] . '" }';
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

		return "{\n\t" . $properties . ",\n\t" . $items . "\n}";
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( wfMessage( 'smw_json_link' )->text() );
		$params['limit']->setDefault( 100 );

		$params['syntax'] = array(
			'type' => 'string',
			'default' => 'complete',
			'message' => 'smw-paramdesc-jsonsyntax',
			'values' => array( 'obsolete', 'basic', 'standard' ),
		);

		$params['prettyprint'] = array(
			'type' => 'boolean',
			'default' => '',
			'message' => 'smw-paramdesc-prettyprint',
		);

		return $params;
	}
}

/**
 * Class representing SMW JSON objects
 *
 * @since 1.8
 *
 * @return array of SMWJSON|array
 */
class SMWJSON {
	protected $results;
	protected $count;

	/**
	 * Constructor
	 *
	 * @param SMWQueryResult $res
	 */
	public function __construct( SMWQueryResult $res ){
		$this->results = $res;
		$this->count   = $res->getCount();
	}

	/**
	 * Standard SMW JSON layer
	 *
	 * The output structure resembles that of the api json format structure
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function getSerialization() {
		return array_merge( SMWDISerializer::getSerializedQueryResult( $this->results ), array ( 'rows' => $this->count ) );
	}

	/**
	 * Basic SMW JSON layer
	 *
	 * This is a convenience layer which is eliminating some overhead from the
	 * standard SMW JSON
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function getBasicSerialization( ) {
		$results = array();
		$printRequests = array();

		foreach ( $this->results->getPrintRequests() as /* SMWPrintRequest */ $printRequest ) {
			$printRequests[$printRequest->getLabel()] = array(
				'label'  => $printRequest->getLabel(),
				'typeid' => $printRequest->getTypeID()
			);
		}

		foreach ( $this->results->getResults() as /* SMWDIWikiPage */ $diWikiPage ) {
			$result = array( );

			foreach ( $this->results->getPrintRequests() as /* SMWPrintRequest */ $printRequest ) {
				$serializationItems = array();
				$resultAarray = new SMWResultArray( $diWikiPage, $printRequest, $this->results->getStore() );

				if ( $printRequest->getMode() === SMWPrintRequest::PRINT_THIS ) {
					$dataItems = $resultAarray->getContent();
					$fulltext = SMWDISerializer::getSerialization( array_shift( $dataItems ) );
					$result  += array ( 'label' => $fulltext["fulltext"] );
				}
				else {
					$serializationItems = array_map(
						array( 'SMWDISerializer', 'getSerialization' ),
						$resultAarray->getContent()
					);

					$type  = $printRequest->getTypeID();
					$items = array();

					foreach ( $serializationItems as $item ) {
						if ( $type == "_wpg" ) {
								$items[] = $item["fulltext"];
						} else {
								$items[] = $item;
						}
					}
					$result[$printRequest->getLabel()] = $items;
				}
			}
			$results[$diWikiPage->getTitle()->getFullText()] = $result;
		}
		return array( 'printrequests' => $printRequests, 'results' => $results, 'rows' => $this->count );
	}

	/**
	 * JSON Encoding
	 *
	 * @since 1.8
	 *
	 * @param $syntax string
	 * @param $isPretty boolean prettify JSON output
	 *
	 * @return string
	*/
	public function getEncoding( $syntax = '' , $isPretty = false ){
		return FormatJSON::encode( $syntax === 'basic' ? $this->getBasicSerialization() : $this->getSerialization(), $isPretty );
	}
}
