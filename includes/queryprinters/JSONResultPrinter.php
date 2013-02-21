<?php

namespace SMW;
use SMWQueryResult, SMWQueryProcessor, SMWQuery;
use FormatJSON;

/**
 * Print links to JSON files representing query results.
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:JSON_format
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
class JSONResultPrinter extends \SMWExportPrinter {

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

			// Serialize queryResult
			$result = FormatJSON::encode(
				array_merge(
					$res->serializeToArray(),
					array ( 'rows' => $res->getCount() )
				), $this->params['prettyprint']
			);

		} else {
			// Create a link that points to the JSON file
			$result = $this->getLink( $res, $outputmode )->getText( $outputmode, $this->mLinker );

			// Code can be viewed as HTML if requested, no more parsing needed
			$this->isHTML = $outputmode == SMW_OUTPUT_HTML;
		}

		return $result;
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

		$params['prettyprint'] = array(
			'type' => 'boolean',
			'default' => '',
			'message' => 'smw-paramdesc-prettyprint',
		);

		return $params;
	}
}

/**
 * SMWJSONResultPrinter
 *
 * @deprecated since SMW 1.9
 */
class_alias( 'SMW\JSONResultPrinter', 'SMWJSONResultPrinter' );