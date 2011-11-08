<?php

/**
 * Static utility class.
 * 
 * @since 1.7
 * 
 * @file SMW_QP_Distributable.php
 * @ingroup SMWQuery
 * 
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SMWDistributablePrinter extends SMWResultPrinter {
	
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
	protected function getResultText( SMWQueryResult $result, $outputmode ) {
		$data = $this->getResults( $result, $outputmode );
		
		if ( count( $data ) == 0 ) {
			return '<span class="error">' . wfMsgForContent( 'srf-warn-empy-chart' ) . '</span>';
			
			// This makes the parser go mad :/
//			global $wgParser;
//			return $wgParser->parse(
//				'{{#info:' . wfMsgForContent( 'srf-warn-empy-chart' ) . '|warning}}',
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
		else if ( $this->params['distributionsort'] == 'desc' ) {
			arsort( $data, SORT_NUMERIC );
		}
		
		if ( $this->params['distributionimit'] !== false ) {
			$data = array_slice( $data, 0, $this->params['distributionimit'] );
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
	 * @param $outputmode
	 * 
	 * @return array label => value
	 */
	protected function getResults( SMWQueryResult $result, $outputmode ) {
		if ( $this->params['distribution'] ) {
			return $this->getDistributionResults( $result, $outputmode );
		}
		else {
			return $this->getNumericResults( $result, $outputmode );
		}
	}
	
	/**
	 * Counts all the occurances of all values in the query result,
	 * and returns an array with as key the value and as value the count.
	 * 
	 * @since 1.7
	 * 
	 * @param SMWQueryResult $res
	 * @param $outputmode
	 * 
	 * @return array label => value
	 */
	protected function getDistributionResults( SMWQueryResult $result, $outputmode ) {
		$values = array();
		
		while ( /* array of SMWResultArray */ $row = $result->getNext() ) { // Objects (pages)
			for ( $i = 0, $n = count( $row ); $i < $n; $i++ ) { // SMWResultArray for a sinlge property 
				while ( ( /* SMWDataValue */ $dataValue = $row[$i]->getNextDataValue() ) !== false ) { // Data values
					
					// Get the HTML for the tag content. Pages are linked, other stuff is just plaintext.
					if ( $dataValue->getTypeID() == '_wpg' ) {
						$value = $dataValue->getTitle()->getText();
					}
					else {
						$value = $dataValue->getShortText( $outputmode, $this->getLinker( false ) );
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
	 * @param $outputmode
	 * 
	 * @return array label => value
	 */
	protected function getNumericResults( SMWQueryResult $res, $outputmode ) {
		$values = array();
		
		// print all result rows
		while ( $row = $res->getNext() ) {
			$name = $row[0]->getNextDataValue()->getShortWikiText();
			
			foreach ( $row as $field ) {
				while ( ( $object = $field->getNextDataValue() ) !== false ) {
					if ( $object->isNumeric() ) { // use numeric sortkey
						$values[$name] = $object->getDataItem()->getNumber();
					}
				}
			}
		}
		
		return $values;
	}
	
	/**
	 * @see SMWResultPrinter::getParameters
	 * @since 1.7
	 */
	public function getParameters() {
		$params = parent::getParameters();
		
		$params['distribution'] = new Parameter( 'distribution', Parameter::TYPE_BOOLEAN, false );
		$params['distribution']->setMessage( 'smw-paramdesc-distribution' );
		
		$params['distributionsort'] = new Parameter( 'distributionsort', Parameter::TYPE_STRING, 'none' );
		$params['distributionsort']->setMessage( 'smw-paramdesc-distributionsort' );
		$params['distributionsort']->addCriteria( new CriterionInArray( 'asc', 'desc', 'none' ) );
		
		$params['distributionimit'] = new Parameter( 'distributionimit', Parameter::TYPE_INTEGER );
		$params['distributionimit']->setDefault( false, false );
		$params['distributionimit']->setMessage( 'smw-paramdesc-distributionimit' );
		$params['distributionimit']->addCriteria( new CriterionInRange( 1, false ) );
		
		return $params;
	}
	
}