<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\ExportPrinter;
use SMW\Query\Query;
use SMW\Query\QueryProcessor;
use SMW\Query\QueryResult;

/**
 * Base class for file export result printers
 *
 * @since 1.8
 * @license GPL-2.0-or-later
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class FileExportPrinter extends ResultPrinter implements ExportPrinter {

	private bool $httpHeader = true;

	/**
	 * @see ExportPrinter::isExportFormat
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	public function isExportFormat(): bool {
		return true;
	}

	/**
	 * @see 3.0
	 */
	public function disableHttpHeader(): void {
		$this->httpHeader = false;
	}

	/**
	 * @see ExportPrinter::outputAsFile
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $queryResult
	 * @param array $params
	 */
	public function outputAsFile( QueryResult $queryResult, array $params ): void {
		$result = $this->getFileResult( $queryResult, $params );

		$this->httpHeader(
			'Content-type: ' . $this->getMimeType( $queryResult ) . '; charset=UTF-8'
		);

		$fileName = $this->getFileName(
			$queryResult
		);

		if ( $fileName !== false ) {
			$utf8Name = rawurlencode( $fileName );
			$fileName = iconv( "UTF-8", "ASCII//TRANSLIT", $fileName );

			$this->httpHeader(
				"content-disposition: attachment; filename=\"$fileName\"; filename*=UTF-8''$utf8Name;"
			);
		}

		echo $result;
	}

	/**
	 * @see ExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $queryResult
	 *
	 * @return string|false
	 */
	public function getFileName( QueryResult $queryResult ): string|false {
		return false;
	}

	/**
	 * File exports use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 *
	 * @param $mode
	 *
	 * @return int
	 */
	public function getQueryMode( $mode ) {
		return $mode == QueryProcessor::SPECIAL_PAGE ? Query::MODE_INSTANCES : Query::MODE_NONE;
	}

	/**
	 * `ResultPrinter::getResult` is marked as final making any attempt to test
	 * this method futile hence we isolate its access to ensure to be able to
	 * verify the access sequence (#4375).
	 */
	protected function getFileResult( QueryResult $queryResult, array $params ) {
		return $this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );
	}

	private function httpHeader( string $string ): void {
		$this->httpHeader ? header( $string ) : '';
	}

}
