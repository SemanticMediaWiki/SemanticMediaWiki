<?php
/**
 * @file
 * @since 1.8
 */

namespace SMW\Test;

use SMW\Tests\MwDBaseUnitTestCase;
use SMWQueryProcessor;

/**
 * Tests for the SMWQueryProcessor class.
 *
 * @since 1.8
 *
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

	/**
	 * @dataProvider rawParamsProvider
	 */
	public function testQuerStringFromRawParameters( $rawParams, $expected ) {

		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, false );

		$this->assertEquals(
			$expected,
			$queryString
		);
	}

	public function rawParamsProvider() {

		$provider[] = array(
			array( 'Foo', 'bar' ),
			'Foobar'
		);

		$provider[] = array(
			array( 'Foo=', 'Bar' ),
			'Bar'
		);

		$provider[] = array(
			array( '[[Foo::Foo=Bar]]', '+Foo' ),
			'[[Foo::Foo=Bar]]'
		);

		$provider[] = array(
			array( '[[Modification date::+]]', '?Modification date=Date', 'limit=1' ),
			'[[Modification date::+]]'
		);

		$provider[] = array(
			array( '?Foo', 'limit=1', '[[Modification date::+]] OR [[Foo::Foo=Bar]]' ),
			'[[Modification date::+]] OR [[Foo::Foo=Bar]]'
		);

		$provider[] = array(
			array( '[[Modification date::+]] OR <q>[[Foo::Bar]]</q>', '?Modification date=Date', '?Foo=F', 'limit=1' ),
			'[[Modification date::+]] OR <q>[[Foo::Bar]]</q>'
		);

		$provider[] = array(
			array( '[[Has url::http://example.org/api.php?action=Foo]]', '?Has url=url', 'limit=1' ),
			'[[Has url::http://example.org/api.php?action=Foo]]'
		);

		// Produced by smw.org, Template:Invert-property
		$provider[] = array(
			array( '[[Located in::Foo]]', 'link=none', 'sep=| ]][[Location of::' ),
			'[[Located in::Foo]]'
		);

		$provider[] = array(
			array( '[[Located in::Foo]]', 'link=none', 'sep=| ]][[Location of::', '[[Has url::http://example.org/api.php?action=Foo]]' ),
			'[[Located in::Foo]][[Has url::http://example.org/api.php?action=Foo]]'
		);

		return $provider;
	}

}
