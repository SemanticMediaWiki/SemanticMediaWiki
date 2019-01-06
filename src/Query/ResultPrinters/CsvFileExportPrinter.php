<?php

namespace SMW\Query\ResultPrinters;

use Sanitizer;
use SMW\Utils\Csv;
use SMWQueryResult as QueryResult;

/**
 * CSV export support
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Nathan R. Yergler
 * @author Markus Krötzsch
 * @author mwjames
 */
class CsvFileExportPrinter extends FileExportPrinter {

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'smw_printername_csv' )->text();
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return 'text/csv';
	}

	/**
	 * @see FileExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {
		return $this->params['filename'];
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

		$definitions['searchlabel']->setDefault(
			$this->msg( 'smw_csv_link' )->inContentLanguage()->text()
		);

		$params[] = [
			'name' => 'sep',
			'message' => 'smw-paramdesc-csv-sep',
			'default' => ',',
		];

		$params['valuesep'] = [
			'message' => 'smw-paramdesc-csv-valuesep',
			'default' => ',',
		];

		$params['showsep'] = [
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-showsep',
		];

		$params[] = [
			'name' => 'filename',
			'message' => 'smw-paramdesc-filename',
			'default' => 'result.csv',
		];

		$params['merge'] = [
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-csv-merge',
		];

		$params['bom'] = [
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-csv-bom',
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		// Always return a link for when the output mode is not a file request,
		// a file request is normally only initiated when resolving the query
		// via Special:Ask
		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getCsvLink( $res, $outputMode );
		}

		$csv = new Csv(
			$this->params['showsep'],
			$this->params['bom']
		);

		return $this->getCsv( $csv, $res );
	}

	private function getCsvLink( QueryResult $res, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputMode == SMW_OUTPUT_HTML;

		$link = $this->getLink(
			$res,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

	private function getCsv( Csv $csv, $res ) {

		$sep = str_replace( '_', ' ', $this->params['sep'] );
		$vsep = str_replace( '_', ' ', $this->params['valuesep'] );

		$header = [];
		$rows = [];

		if ( $this->mShowHeaders ) {
			foreach ( $res->getPrintRequests() as $pr ) {
				$header[] = $pr->getLabel();
			}
		}

		while ( $row = $res->getNext() ) {
			$row_items = [];

			foreach ( $row as /* SMWResultArray */ $field ) {
				$growing = [];

				while ( ( $object = $field->getNextDataValue() ) !== false ) {
					$growing[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
				}

				$row_items[] = implode( $vsep, $growing );
			}

			$rows[] = $row_items;
		}

		if ( $this->params['merge'] === true ) {
			$rows = $csv->merge( $rows, $vsep );
		}

		return $csv->toString(
			$header,
			$rows,
			$sep
		);
	}

}
