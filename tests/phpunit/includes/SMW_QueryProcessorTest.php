<?php

/**
 * Tests for the SMWQueryProcessor class.
 *
 * @file
 * @since storerewrite
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWQueries
 *
 * @author Nischay Nahata
 */
class SMWQueryProcessorTest extends MediaWikiTestCase {

	public function createQueryDataProvider() {
		return array(
			array( '[[Modification date::+]]|?Modification date|sort=Modification date|order=desc' ),
		);
	}

	/**
	* @dataProvider createQueryDataProvider
	*/
	public function testCreateQuery( $query ) {
		// TODO: this prevents doing [[Category:Foo||bar||baz]], must document.
		$rawParams = explode( '|', $query );
		$queryString = '';
		$printouts = array();
		$parameters;
		
		SMWQueryProcessor::processFunctionParams( $rawParams, $queryString, $parameters, $printouts );
		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );
		$parameters = SMWQueryProcessor::getProcessedParams( $parameters, $printouts );
		
		$this->assertInstanceOf(
			'SMWQuery',
			SMWQueryProcessor::createQuery(
				$queryString,
				$parameters,
				SMWQueryProcessor::SPECIAL_PAGE,
				'',
				$printouts
			),
			"Result should be instance of SMWQuery."
		);
	}
}