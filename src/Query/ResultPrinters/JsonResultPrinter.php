<?php

namespace SMW\Query\ResultPrinters;

use SMWQueryResult as QueryResult;

/**
 * Print links to JSON files representing query results.
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:JSON_format
 *
 * @license GNU GPL v2 or later
 * @since 1.5.3
 *
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Fabian Howahl
 */
class JsonResultPrinter extends FileExportPrinter {

	// Rename to JsonFileResultPrinter

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'smw_printername_json' )->text();
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return 'application/json';
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {

		if ( $this->params['filename'] === '' ) {
			return 'result.json';
		}

		if ( substr( $this->params['filename'], -5 ) === '.json' ) {
			return str_replace( ' ', '_', $this->params['filename'] ) ;
		}

		return str_replace( ' ', '_', $this->params['filename'] . '.json' );
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
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

		$params[] = [
			'name' => 'filename',
			'message' => 'smw-paramdesc-filename',
			'default' => 'result.json',
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getJsonLink( $res, $outputMode );
		}

		// No results, just bailout
		if ( $res->getCount() == 0 ){
			return $this->params['default'] !== '' ? $this->params['default'] : '';
		}

		return $this->buildJSON( $res, $outputMode );
	}

	private function getJsonLink( QueryResult $res, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputMode == SMW_OUTPUT_HTML;

		$link = $this->getLink(
			$res,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

	private function buildJSON( QueryResult $res, $outputMode ) {

		$flags = $this->params['prettyprint'] ? JSON_PRETTY_PRINT : 0;
		$flags = $flags | ( $this->params['unescape'] ? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES : 0 );

		if ( isset( $this->params['type'] ) && $this->params['type'] === 'simple' ) {
			$result = $this->buildSimpleList( $res );
		} else {
			$result = array_merge( $res->serializeToArray(), [ 'rows' => $res->getCount() ] );
		}

		return json_encode( $result, $flags );
	}

	private function buildSimpleList( $res ) {

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
