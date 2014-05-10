<?php
/**
 * @file
 * @since 1.8
 * @ingroup SMW
 * @ingroup Test
 */

namespace SMW\Test;

use SMW\Tests\MwDBaseUnitTestCase;
use SMWQueryProcessor;

/**
 * Tests for the SMWQueryProcessor class.
 *
 * @since 1.8
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQueries
 * @group SMWQueryProcessorTest
 *
 * @author Nischay Nahata
 */
class SMWQueryProcessorTest extends MwDBaseUnitTestCase {

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

		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, false );

		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

		$parameters = SMWQueryProcessor::getProcessedParams( $parameters, $printouts );

		$this->assertInstanceOf(
			'\SMWQuery',
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