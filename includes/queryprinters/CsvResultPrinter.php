<?php

namespace SMW;

use Sanitizer;
use SMWQueryResult;

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

		if ( $outputMode == SMW_OUTPUT_FILE ) { // make CSV file
			$csv = fopen( 'php://temp', 'r+' );
			$sep = str_replace( '_', ' ', $this->params['sep'] );

			if ( $this->params['showsep'] ) {
				fputs( $csv, "sep=" . $sep . "\n" );
			}

			if ( $this->mShowHeaders ) {
				$header_items = array();

				foreach ( $res->getPrintRequests() as $pr ) {
					$header_items[] = $pr->getLabel();
				}

				fputcsv( $csv, $header_items, $sep );
			}

			while ( $row = $res->getNext() ) {
				$row_items = array();

				foreach ( $row as /* SMWResultArray */ $field ) {
					$growing = array();

					while ( ( $object = $field->getNextDataValue() ) !== false ) {
						$growing[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
					}

					$row_items[] = implode( ',', $growing );
				}

				fputcsv( $csv, $row_items, $sep );
			}

			rewind( $csv );
			$result .= stream_get_contents( $csv );
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

		$definitions['limit']->setDefault( 100 );

		$params[] = array(
			'name' => 'sep',
			'message' => 'smw-paramdesc-csv-sep',
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

		return $params;
	}
}
