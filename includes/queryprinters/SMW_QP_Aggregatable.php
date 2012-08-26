<?php

/**
 * Result printer that supports the distribution parameter,
 * and related parameters. It allows the user to choose between
 * regular behaviour or getting a distribution of values.
 *
 * For example, this result set: foo bar baz foo bar bar ohi
 * Will be turned into
 * * bar (3)
 * * foo (2)
 * * baz (1)
 * * ohi (1)
 *
 * @since 1.7
 *
 * @file SMW_QP_Aggregatable.php
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SMWAggregatablePrinter extends SMWResultPrinter {

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
	 * (non-PHPdoc)
	 * @see SMWResultPrinter::getResultText()
	 */
	protected function getResultText( SMWQueryResult $result, $outputMode ) {
		$data = $this->getResults( $result, $outputMode );

		if ( count( $data ) == 0 ) {
			// This is wikitext, so no escaping needed.
			return '<span class="error">' . wfMessage( 'srf-warn-empy-chart' )->inContentLanguage()->text() . '</span>';

			// This makes the parser go mad :/
//			global $wgParser;
//			return $wgParser->parse(
//				'{{#info:' . wfMessage( 'srf-warn-empy-chart' )->inContentLanguage()->text() . '|warning}}',
//				Title::newMainPage(),
//				( new ParserOptions() )
//			)->getText();
		}
		else {
			$this->applyDistributionParams( $data );
			$this->addResources();
			return $this->getFormatOutput( $data );
		}
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
		}
		elseif ( $this->params['distributionsort'] == 'desc' ) {
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
	 * @param SMWQueryResult $result
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getResults( SMWQueryResult $result, $outputMode ) {
		if ( $this->params['distribution'] ) {
			return $this->getDistributionResults( $result, $outputMode );
		}
		else {
			return $this->getNumericResults( $result, $outputMode );
		}
	}

	/**
	 * Counts all the occurrences of all values in the query result,
	 * and returns an array with as key the value and as value the count.
	 *
	 * @since 1.7
	 *
	 * @param SMWQueryResult $result
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getDistributionResults( SMWQueryResult $result, $outputMode ) {
		$values = array();

		while ( /* array of SMWResultArray */ $row = $result->getNext() ) { // Objects (pages)
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
	 * @param SMWQueryResult $res
	 * @param $outputMode
	 *
	 * @return array label => value
	 */
	protected function getNumericResults( SMWQueryResult $res, $outputMode ) {
		$values = array();

		// print all result rows
		while ( $row = $res->getNext() ) {
			$dataValue = $row[0]->getNextDataValue();

			if ( $dataValue !== false ) {
				$name = $dataValue->getShortWikiText();

				foreach ( $row as $field ) {
					while ( ( /* SMWDataItem */ $dataItem = $field->getNextDataItem() ) !== false ) {
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
	 * @param SMWDataItem $dataItem
	 * @param array $values
	 * @param string $name
	 */
	protected function addNumbersForDataItem( SMWDataItem $dataItem, array &$values, $name ) {
		switch ( $dataItem->getDIType() ) {
			case SMWDataItem::TYPE_NUMBER:
				$values[$name] = $dataItem->getNumber();
				break;
			case SMWDataItem::TYPE_CONTAINER:
				foreach ( $dataItem->getDataItems() as $di ) {
					$this->addNumbersForDataItem( $di, $values, $name );
				}
				break;
			default:
		}
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions['distribution'] = array(
			'name' => 'distribution',
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-distribution',
		);

		$definitions['distributionsort'] = array(
			'name' => 'distributionsort',
			'type' => 'string',
			'default' => 'none',
			'message' => 'smw-paramdesc-distributionsort',
			'values' => array( 'asc', 'desc', 'none' ),
		);

		$definitions['distributionlimit'] = array(
			'name' => 'distributionlimit',
			'type' => 'integer',
			'default' => false,
			'manipulatedefault' => false,
			'message' => 'smw-paramdesc-distributionlimit',
			'lowerbound' => 1,
		);

		return $definitions;
	}

}
