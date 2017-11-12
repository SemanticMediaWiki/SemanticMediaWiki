<?php

namespace SMW;

use Sanitizer;
use SMWQueryResult;
use SMW\Utils\Csv;

/**
 * CSV export for SMW Queries
 *
 * @since 1.9
 *
 *
 * @license GNU GPL v2+
 * @author Nathan R. Yergler
 * @author Markus Krötzsch
 */

/**
 * Printer class for generating CSV output
 *
 * @ingroup QueryPrinter
 */
class CsvResultPrinter extends FileExportPrinter {

	/**
	 * @codeCoverageIgnore
	 *
	 * @return string
	 */
	public function getName() {
		return $this->msg( 'smw_printername_csv' )->text();
	}

	/**
	 * @see SMWIExportPrinter::getMimeType
	 * @codeCoverageIgnore
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string
	 */
	public function getMimeType( SMWQueryResult $queryResult ) {
		return 'text/csv';
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
		return $this->params['filename'];
	}

	protected function getResultText( SMWQueryResult $res, $outputMode ) {
		$result = '';
		$header = [];

		if ( $outputMode == SMW_OUTPUT_FILE ) { // make CSV file

			$csv = new Csv(
				$this->params['showsep'],
				$this->params['bom']
			);

			$sep = str_replace( '_', ' ', $this->params['sep'] );
			$vsep = str_replace( '_', ' ', $this->params['valuesep'] );

			if ( $this->mShowHeaders ) {
				foreach ( $res->getPrintRequests() as $pr ) {
					$header[] = $pr->getLabel();
				}
			}

			$rows = [];

			while ( $row = $res->getNext() ) {
				$row_items = array();

				foreach ( $row as /* SMWResultArray */ $field ) {
					$growing = array();

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

			$result .= $csv->toString(
				$header,
				$rows,
				$sep
			);
		} else { // just make link to feed
			$result .= $this->getLink( $res, $outputMode )->getText( $outputMode, $this->mLinker );
			$this->isHTML = ( $outputMode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
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

		$definitions['searchlabel']->setDefault( $this->msg( 'smw_csv_link' )->inContentLanguage()->text() );

		$params[] = array(
			'name' => 'sep',
			'message' => 'smw-paramdesc-csv-sep',
			'default' => ',',
		);

		$params['valuesep'] = array(
			'message' => 'smw-paramdesc-csv-valuesep',
			'default' => ',',
		);

		$params['showsep'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-showsep',
		);

		$params[] = array(
			'name' => 'filename',
			'message' => 'smw-paramdesc-filename',
			'default' => 'result.csv',
		);

		$params['merge'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-csv-merge',
		);

		$params['bom'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-csv-bom',
		);

		return $params;
	}

}
