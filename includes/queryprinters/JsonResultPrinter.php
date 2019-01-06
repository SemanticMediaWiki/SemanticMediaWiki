<?php

namespace SMW;

use FormatJson;
use SMWQueryResult;

/**
 * Print links to JSON files representing query results.
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:JSON_format
 *
 * @since 1.5.3
 *
 *
 * @license GNU GPL v2 or later
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Fabian Howahl
 */

/**
 * Print links to JSON files representing query results.
 *
 * @ingroup QueryPrinter
 */
class JsonResultPrinter extends FileExportPrinter {

	/**
	 * Returns human readable label for this printer
	 * @codeCoverageIgnore
	 *
	 * @return string
	 */
	public function getName() {
		return $this->msg( 'smw_printername_json' )->text();
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
		return 'application/json';
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
		}

		return 'result.json';
	}

	/**
	 * Returns a filename that is to be sent to the caller
	 *
	 * @param SMWQueryResult $res
	 * @param $outputMode integer
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $res, $outputMode ) {

		if ( $outputMode == SMW_OUTPUT_FILE ) {

			// No results, just bailout
			if ( $res->getCount() == 0 ){
				return $this->params['default'] !== '' ? $this->params['default'] : '';
			}

			$flags = $this->params['prettyprint'] ? JSON_PRETTY_PRINT : 0;
			$flags = $flags | ( $this->params['unescape'] ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0 );

			// Serialize queryResult
			if ( isset( $this->params['type'] ) && $this->params['type'] === 'simple' ) {
				$result = $this->serializeAsSimpleList( $res );
			} else {
				$result = array_merge(
					$res->serializeToArray(),
					[ 'rows' => $res->getCount() ]
				);
			}

			$result = json_encode(
				$result,
				$flags
			);

		} else {
			// Create a link that points to the JSON file
			$result = $this->getLink( $res, $outputMode )->getText( $outputMode, $this->mLinker );

			// Code can be viewed as HTML if requested, no more parsing needed
			$this->isHTML = $outputMode == SMW_OUTPUT_HTML;
		}

		return $result;
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 * @codeCoverageIgnore
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( $this->msg( 'smw_json_link' )->inContentLanguage()->text() );

		$params['limit']->setDefault( 100 );

		$params['type'] = [
			'values' => [ 'simple', 'full' ],
			'default' => 'full',
			'message' => 'smw-paramdesc-json-type',
		];

		$params['prettyprint'] = [
			'type' => 'boolean',
			'default' => '',
			'message' => 'smw-paramdesc-prettyprint',
		];

		$params['unescape'] = [
			'type' => 'boolean',
			'default' => '',
			'message' => 'smw-paramdesc-json-unescape',
		];

		return $params;
	}

	private function serializeAsSimpleList( $res ) {

		$result = [];

		while ( $row = $res->getNext() ) {
			$item = [];
			$subject = '';

			foreach ( $row as /* SMWResultArray */ $field ) {
				$label = $field->getPrintRequest()->getLabel();

				if ( $label === '' ) {
					continue;
				}

				$values = [];
				$subject = $field->getResultSubject()->getHash();

				while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
					$values[] = $dataValue->getWikiValue();
				}

				$item[$label] = $values;
			}

			if ( $this->params['mainlabel'] === '-' || $subject === '' ) {
				$result[] = $item;
			} else {
				$result[$subject] = $item;
			}
		}

		return $result;
	}

}
