<?php

namespace SMW\Query\ResultPrinters;

use SMWDataItem as DataItem;
use SMWQueryResult as QueryResult;

/**
 * Abstract class that supports the aggregation and distributive calculation
 * of numerical data.
 *
 * Abstract class that supports the aggregation and distributive calculation
 * of numerical data. Supports the distribution parameter, and related
 * parameters that allows the user to choose between regular behavior or
 * generating a distribution of values.
 *
 * For example, this result set: foo bar baz foo bar bar ohi
 * Will be turned into
 * * bar (3)
 * * foo (2)
 * * baz (1)
 * * ohi (1)
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class AggregatablePrinter extends ResultPrinter {

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions['distribution'] = [
			'name' => 'distribution',
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-distribution',
		];

		$definitions['distributionsort'] = [
			'name' => 'distributionsort',
			'type' => 'string',
			'default' => 'none',
			'message' => 'smw-paramdesc-distributionsort',
			'values' => [ 'asc', 'desc', 'none' ],
		];

		$definitions['distributionlimit'] = [
			'name' => 'distributionlimit',
			'type' => 'integer',
			'default' => false,
			'manipulatedefault' => false,
			'message' => 'smw-paramdesc-distributionlimit',
			'lowerbound' => 1,
		];

		$definitions['aggregation'] = [
			'message' => 'smw-paramdesc-aggregation',
			'default' => 'subject',
			'values' => [ 'property', 'subject' ],
		];

		return $definitions;
	}

	/**
	 * Create the formats output given the result data and return it.
	 *
	 * @since 1.7
	 *
	 * @param array $data label => value
	 */
	protected abstract function getFormatOutput( array $data );

	/**
	 * Method gets called right before the result is returned
	 * in case there are values to display. It is meant for
	 * adding resources such as JS and CSS only needed for this
	 * format when it has actual values.
	 *
	 * @since 1.7
	 */
	protected function addResources() {}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {
		$data = $this->getResults( $queryResult, $outputMode );

		if ( $data === [] ) {
			$queryResult->addErrors( [ 'smw-qp-empty-data' ] );
			return '';
		}

		$this->applyDistributionParams( $data );
		$this->addResources();

		return $this->getFormatOutput( $data );
	}

	/**
	 * Apply the distribution specific parameters.
	 *
	 * @since 1.7
	 *
	 * @param array $data
	 */
	protected function applyDistributionParams( array &$data ) {

		if ( $this->params['distributionsort'] == 'asc' ) {
			asort( $data, SORT_NUMERIC );
		} elseif ( $this->params['distributionsort'] == 'desc' ) {
			arsort( $data, SORT_NUMERIC );
		}

		if ( $this->params['distributionlimit'] !== false ) {
			$data = array_slice( $data, 0, $this->params['distributionlimit'], true );
		}
	}

	/**
	 * Gets and processes the results so they can be fed directly to the
	 * getFormatOutput method. They are returned as an array with the keys
	 * being the labels and the values being their corresponding (numeric) values.
	 *
	 * @since 1.7
	 *
	 * @param QueryResult $result
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getResults( QueryResult $queryResult, $outputMode ) {

		if ( $this->params['distribution'] ) {
			return $this->getDistributionResults( $queryResult, $outputMode );
		}

		return $this->getNumericResults( $queryResult, $outputMode );
	}

	/**
	 * Counts all the occurrences of all values in the query result,
	 * and returns an array with as key the value and as value the count.
	 *
	 * @since 1.7
	 *
	 * @param QueryResult $queryResult
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getDistributionResults( QueryResult $queryResult, $outputMode ) {
		$values = [];

		while ( /* array of SMWResultArray */ $row = $queryResult->getNext() ) { // Objects (pages)
			for ( $i = 0, $n = count( $row ); $i < $n; $i++ ) { // SMWResultArray for a sinlge property
				while ( ( /* SMWDataValue */ $dataValue = $row[$i]->getNextDataValue() ) !== false ) { // Data values

					// Get the HTML for the tag content. Pages are linked, other stuff is just plaintext.
					if ( $dataValue->getTypeID() == '_wpg' ) {
						$value = $dataValue->getTitle()->getText();
					}
					else {
						$value = $dataValue->getShortText( $outputMode, $this->getLinker( false ) );
					}

					if ( !array_key_exists( $value, $values ) ) {
						$values[$value] = 0;
					}

					$values[$value]++;
				}
			}
		}

		return $values;
	}

	/**
	 * Returns an array with the numerical data in the query result.
	 *
	 * @since 1.7
	 *
	 * @param QueryResult $queryResult
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getNumericResults( QueryResult $queryResult, $outputMode ) {
		$values = [];

		// print all result rows
		while ( $subject = $queryResult->getNext() ) {
			$dataValue = $subject[0]->getNextDataValue();

			if ( $dataValue !== false ) {
				$name = $dataValue->getShortWikiText();

				foreach ( $subject as $field ) {

					// Use the aggregation parameter to determine the source of
					// the number composition
					if ( $this->params['aggregation'] === 'property' ) {
						$name = $field->getPrintRequest()->getLabel();
					}

					// Aggregated array key depends on the mainlabel which is
					// either the subject or a printout property
					if ( $this->params['mainlabel'] === '-' ) {

						// In case of '-', getNextDataValue() already shifted the
						// array forward which means the first column
						// ( $subject[0] == $field ) contains a possible value
						// and has to be collected as well
						if ( ( $subject[0] == $field ) && $dataValue->getDataItem()->getDIType() === DataItem::TYPE_NUMBER ) {
							$value = $dataValue->getDataItem( )->getNumber();
							$values[$name] = isset( $values[$name] ) ?  $values[$name] + $value : $value;
						}
					}

					while ( ( /* DataItem */ $dataItem = $field->getNextDataItem() ) !== false ) {
						$this->addNumbersForDataItem( $dataItem, $values, $name );
					}
				}
			}
		}

		return $values;
	}

	/**
	 * Adds all numbers contained in a dataitem to the list.
	 *
	 * @since 1.7
	 *
	 * @param DataItem $dataItem
	 * @param array $values
	 * @param string $name
	 */
	protected function addNumbersForDataItem( DataItem $dataItem, array &$values, $name ) {
		switch ( $dataItem->getDIType() ) {
			case DataItem::TYPE_NUMBER:
				// Collect and aggregate values for the same array key
				$value = $dataItem->getNumber();
				if ( !isset( $values[$name] ) ) {
					$values[$name] = 0;
				}
				$values[$name] += $value;
				break;
			case DataItem::TYPE_CONTAINER:
				foreach ( $dataItem->getDataItems() as $di ) {
					$this->addNumbersForDataItem( $di, $values, $name );
				}
				break;
			default:
		}
	}

}
