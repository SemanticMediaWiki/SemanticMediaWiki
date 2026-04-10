<?php

namespace SMW\Query\ResultPrinters;

use MediaWiki\Parser\Sanitizer;
use SMW\Query\QueryResult;
use SMW\Utils\Csv;

/**
 * CSV export support
 *
 * @license GPL-2.0-or-later
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
	public function getMimeType( QueryResult $queryResult ): string {
		return 'text/csv';
	}

	/**
	 * @see FileExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ): string {
		return $this->params['filename'];
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ): array {
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

	private function getCsv( Csv $csv, QueryResult $res ): string|false {
		$sep = str_replace( '_', ' ', $this->params['sep'] );
		$vsep = str_replace( '_', ' ', $this->params['valuesep'] );

		$header = [];
		$rows = [];

		if ( $this->mShowHeaders ) {
			foreach ( $res->getPrintRequests() as $pr ) {
				$header[] = $pr->getLabel();
			}
		}

		$row = $res->getNext();
		while ( $row ) {
			$row_items = [];

			foreach ( $row as /* ResultArray */ $field ) {
				$growing = [];

				$object = $field->getNextDataValue();
				while ( $object !== false ) {
					$growing[] = Sanitizer::decodeCharReferences( $object->getShortWikiText() );
					$object = $field->getNextDataValue();
				}

				$row_items[] = implode( $vsep, $growing );
			}

			$rows[] = $row_items;
			$row = $res->getNext();
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
