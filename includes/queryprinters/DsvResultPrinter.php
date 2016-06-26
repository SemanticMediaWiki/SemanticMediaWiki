<?php

namespace SMW;

use Sanitizer;
use SMWQueryResult;

/**
 * Result printer to print results in UNIX-style DSV (deliminter separated value) format.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DsvResultPrinter extends FileExportPrinter {

	protected $separator = ':';
	protected $fileName = 'result.dsv';

	/**
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.6
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		// Do not allow backspaces as delimiter, as they'll break stuff.
		if ( trim( $params['separator'] ) != '\\' ) {
			$this->separator = trim( $params['separator'] );
		}

		$this->fileName = str_replace( ' ', '_', $params['filename'] );
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
		return 'text/dsv';
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
		return $this->fileName;
	}

	public function getName() {
		return wfMessage( 'smw_printername_dsv' )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputMode ) {
		if ( $outputMode == SMW_OUTPUT_FILE ) { // Make the DSV file.
			return $this->getResultFileContents( $res );
		}
		else { // Create a link pointing to the DSV file.
			return $this->getLinkToFile( $res, $outputMode );
		}
	}

	/**
	 * Returns the query result in DSV.
	 *
	 * @since 1.6
	 *
	 * @param SMWQueryResult $res
	 *
	 * @return string
	 */
	protected function getResultFileContents( SMWQueryResult $queryResult ) {
		$lines = array();

		if ( $this->mShowHeaders ) {
			$headerItems = array();

			foreach ( $queryResult->getPrintRequests() as $printRequest ) {
				$headerItems[] = $printRequest->getLabel();
			}

			$lines[] = $this->getDSVLine( $headerItems );
		}

		// Loop over the result objects (pages).
		while ( $row = $queryResult->getNext() ) {
			$rowItems = array();

			/**
			 * Loop over their fields (properties).
			 * @var SMWResultArray $field
			 */
			foreach ( $row as $field ) {
				$itemSegments = array();

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

	/**
	 * Returns a single DSV line.
	 *
	 * @since 1.6
	 *
	 * @param array $fields
	 *
	 * @return string
	 */
	protected function getDSVLine( array $fields ) {
		return implode( $this->separator, array_map( array( $this, 'encodeDSV' ), $fields ) );
	}

	/**
	 * Encodes a single DSV.
	 *
	 * @since 1.6
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function encodeDSV( $value ) {
		// TODO
		// \nnn or \onnn or \0nnn for the character with octal value nnn
		// \xnn for the character with hexadecimal value nn
		// \dnnn for the character with decimal value nnn
		// \unnnn for a hexadecimal Unicode literal.
		return str_replace(
			array( '\n', '\r', '\t', '\b', '\f', '\\', $this->separator ),
			array( "\n", "\r", "\t", "\b", "\f", '\\\\', "\\$this->separator" ),
			$value
		);
	}

	/**
	 * Returns html for a link to a query that returns the DSV file.
	 *
	 * @since 1.6
	 *
	 * @param SMWQueryResult $res
	 * @param $outputMode
	 *
	 * @return string
	 */
	protected function getLinkToFile( SMWQueryResult $res, $outputMode ) {
		// yes, our code can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = ( $outputMode == SMW_OUTPUT_HTML );
		return $this->getLink( $res, $outputMode )->getText( $outputMode, $this->mLinker );
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( wfMessage( 'smw_dsv_link' )->text() );

		$params['limit']->setDefault( 100 );

		$params[] = array(
			'name' => 'separator',
			'message' => 'smw-paramdesc-dsv-separator',
			'default' => $this->separator,
			'aliases' => 'sep',
		);

		$params[] = array(
			'name' => 'filename',
			'message' => 'smw-paramdesc-dsv-filename',
			'default' => $this->fileName,
		);

		return $params;
	}

}
