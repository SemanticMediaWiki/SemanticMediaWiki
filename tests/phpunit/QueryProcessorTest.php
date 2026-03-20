<?php
/**
 * @file
 * @since 1.8
 */

namespace SMW\Tests;

use SMW\Query\Exception\ResultFormatNotFoundException;
use SMW\Query\Query;
use SMW\Query\QueryProcessor;
use SMW\Query\ResultPrinter;

/**
 * Tests for the QueryProcessor class.
 *
 * @since 1.8
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQueries
 * @group SMWQueryProcessorTest
 * @group Database
 *
 * @author Nischay Nahata
 */
class QueryProcessorTest extends SMWIntegrationTestCase {

	public function createQueryDataProvider() {
		return [
			[ '[[Modification date::+]]|?Modification date|sort=Modification date|order=desc' ],
		];
	}

	/**
	 * @dataProvider resultAliasDataProvider
	 */
	public function testGetResultPrinter_MatchAlias( $alias ) {
		$this->assertInstanceOf(
			ResultPrinter::class,
			QueryProcessor::getResultPrinter( $alias )
		);
	}

	public function resultAliasDataProvider() {
		foreach ( $GLOBALS['smwgResultAliases'] as $format => $aliases ) {
			foreach ( $aliases as $alias ) {
				yield [ $alias ];
			}
		}
	}

	public function testGetResultPrinter_ThrowsException() {
		$this->expectException( ResultFormatNotFoundException::class );
		QueryProcessor::getResultPrinter( 'unknown_format' );
	}

	/**
	 * @dataProvider createQueryDataProvider
	 */
	public function testCreateQuery( $query ) {
		// TODO: this prevents doing [[Category:Foo||bar||baz]], must document.
		$rawParams = explode( '|', $query );

		[ $queryString, $parameters, $printouts ] = QueryProcessor::getComponentsFromFunctionParams( $rawParams, false );

		QueryProcessor::addThisPrintout( $printouts, $parameters );

		$parameters = QueryProcessor::getProcessedParams( $parameters, $printouts );

		$this->assertInstanceOf(
			Query::class,
			QueryProcessor::createQuery(
				$queryString,
				$parameters,
				QueryProcessor::SPECIAL_PAGE,
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
		[ $queryString, $parameters, $printouts ] = QueryProcessor::getComponentsFromFunctionParams( $rawParams, false );

		$this->assertEquals(
			$expected,
			$queryString
		);
	}

	public function rawParamsProvider() {
		$provider[] = [
			[ 'Foo', 'bar' ],
			'Foobar'
		];

		$provider[] = [
			[ 'Foo=', 'Bar' ],
			'Bar'
		];

		$provider[] = [
			[ '[[Foo::Foo=Bar]]', '+Foo' ],
			'[[Foo::Foo=Bar]]'
		];

		$provider[] = [
			[ '[[Modification date::+]]', '?Modification date=Date', 'limit=1' ],
			'[[Modification date::+]]'
		];

		$provider[] = [
			[ '?Foo', 'limit=1', '[[Modification date::+]] OR [[Foo::Foo=Bar]]' ],
			'[[Modification date::+]] OR [[Foo::Foo=Bar]]'
		];

		$provider[] = [
			[ '[[Modification date::+]] OR <q>[[Foo::Bar]]</q>', '?Modification date=Date', '?Foo=F', 'limit=1' ],
			'[[Modification date::+]] OR <q>[[Foo::Bar]]</q>'
		];

		$provider[] = [
			[ '[[Has url::http://example.org/api.php?action=Foo]]', '?Has url=url', 'limit=1' ],
			'[[Has url::http://example.org/api.php?action=Foo]]'
		];

		// Produced by smw.org, Template:Invert-property
		$provider[] = [
			[ '[[Located in::Foo]]', 'link=none', 'sep=| ]][[Location of::' ],
			'[[Located in::Foo]]'
		];

		$provider[] = [
			[ '[[Located in::Foo]]', 'link=none', 'sep=| ]][[Location of::', '[[Has url::http://example.org/api.php?action=Foo]]' ],
			'[[Located in::Foo]][[Has url::http://example.org/api.php?action=Foo]]'
		];

		$provider[] = [
			[ '[[This has a = in it]]', 'link=none', 'sep=| ]][[Location of::', '[[Has url::http://example.org/api.php?action=Foo]]' ],
			'[[This has a = in it]][[Has url::http://example.org/api.php?action=Foo]]'
		];

		return $provider;
	}

}
