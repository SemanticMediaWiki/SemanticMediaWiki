<?php

namespace SMW\Query\ResultPrinters;

use Sanitizer;
use SMWQueryResult  as QueryResult;

/**
 * Result printer to print results in UNIX-style DSV (deliminter separated value)
 * format.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DsvResultPrinter extends FileExportPrinter {

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return wfMessage( 'smw_printername_dsv' )->text();
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return 'text/dsv';
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
			return 'result.dsv';
		}

		if ( substr( $this->params['filename'], -4 ) === '.dsv' ) {
			return  str_replace( ' ', '_', $this->params['filename'] );
		}

		return  str_replace( ' ', '_', $this->params['filename'] . '.dsv' );
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

		$params['searchlabel']->setDefault( wfMessage( 'smw_dsv_link' )->text() );

		$params['limit']->setDefault( 100 );

		$params[] = [
			'name' => 'separator',
			'message' => 'smw-paramdesc-dsv-separator',
			'default' => ':',
			'aliases' => 'sep',
		];

		$params[] = [
			'name' => 'filename',
			'message' => 'smw-paramdesc-dsv-filename',
			'default' => 'result.dsv',
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getDsvLink( $queryResult, $outputMode );
		}

		return $this->buildContents( $queryResult );
	}

	private function getDsvLink( QueryResult $queryResult, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = ( $outputMode == SMW_OUTPUT_HTML );

		$link = $this->getLink(
			$queryResult,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

	private function buildContents( QueryResult $queryResult ) {
		$lines = [];

		// Do not allow backspaces as delimiter, as they'll break stuff.
		if ( trim( $this->params['separator'] ) != '\\' ) {
			$this->params['separator'] = trim( $this->params['separator'] );
		}

		/**
		 * @var ResultPrinter::mShowHeaders
		 */
		$showHeaders = $this->mShowHeaders;

		if ( $showHeaders ) {
			$headerItems = [];

			foreach ( $queryResult->getPrintRequests() as $printRequest ) {
				$headerItems[] = $printRequest->getLabel();
			}

			$lines[] = $this->getDSVLine( $headerItems );
		}

		// Loop over the result objects (pages).
		while ( $row = $queryResult->getNext() ) {
			$rowItems = [];

			/**
			 * Loop over their fields (properties).
			 * @var SMWResultArray $field
			 */
			foreach ( $row as $field ) {
				$itemSegments = [];

				// Loop over all values for the property.
				while ( ( $object = $field->getNextDataValue() ) !== false ) {
					$itemSegments[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
				}

				// Join all values into a single string, separating them with comma's.
				$rowItems[] = implode( ',', $itemSegments );
			}

			$lines[] = $this->getDSVLine( $rowItems );
		}

		return implode( "\n", $lines );
	}

	private function getDSVLine( array $fields ) {
		return implode( $this->params['separator'], array_map( [ $this, 'encodeDSV' ], $fields ) );
	}

	private function encodeDSV( $value ) {
		$sep = $this->params['separator'];
		// TODO
		// \nnn or \onnn or \0nnn for the character with octal value nnn
		// \xnn for the character with hexadecimal value nn
		// \dnnn for the character with decimal value nnn
		// \unnnn for a hexadecimal Unicode literal.
		return str_replace(
			[ '\n', '\r', '\t', '\b', '\f', '\\', $sep ],
			[ "\n", "\r", "\t", "\b", "\f", '\\\\', "\\$sep" ],
			$value
		);
	}

}
