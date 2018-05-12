<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\ExportPrinter;
use SMWQuery;
use SMWQueryProcessor;
use SMWQueryResult;

/**
 * Base class for file export result printers
 *
 * @since 1.8
 * @license GNU GPL v2+
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class FileExportPrinter extends ResultPrinter implements ExportPrinter {

	/**
	 * @var boolean
	 */
	private $httpHeader = true;

	/**
	 * @see ExportPrinter::isExportFormat
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function isExportFormat() {
		return true;
	}

	/**
	 * @see 3.0
	 */
	public function disableHttpHeader() {
		$this->httpHeader = false;
	}

	/**
	 * @see ExportPrinter::outputAsFile
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 * @param array $params
	 */
	public function outputAsFile( SMWQueryResult $queryResult, array $params ) {
		$result = $this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );

		if ( $this->httpHeader ) {
			header( 'Content-type: ' . $this->getMimeType( $queryResult ) . '; charset=UTF-8' );
		}

		$fileName = $this->getFileName( $queryResult );

		if ( $fileName !== false ) {
			$utf8Name = rawurlencode( $fileName );
			$fileName = iconv( "UTF-8", "ASCII//TRANSLIT", $fileName );

			if ( $this->httpHeader ) {
				header( "content-disposition: attachment; filename=\"$fileName\"; filename*=UTF-8''$utf8Name;" );
			}
		}

		echo $result;
	}

	/**
	 * @see ExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string|boolean
	 */
	public function getFileName( SMWQueryResult $queryResult ) {
		return false;
	}

	/**
	 * File exports use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 *
	 * @param $mode
	 *
	 * @return integer
	 */
	public function getQueryMode( $mode ) {
		return $mode == SMWQueryProcessor::SPECIAL_PAGE ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

}
