<?php

namespace SMW\Tests\Query;

use SMW\Query\QueryComparator;

/**
 * @covers \SMW\Query\QueryComparator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryComparatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$comparatorList = '';

		$this->assertInstanceOf(
			'\SMW\Query\QueryComparator',
			new QueryComparator( $comparatorList, false )
		);

		$this->assertInstanceOf(
			'\SMW\Query\QueryComparator',
			QueryComparator::getInstance()
		);
	}

	/**
	 * @dataProvider stringComparatorProvider
	 */
	public function testGetComparatorFromString( $stringComparator, $expected ) {

		$comparatorList = '';

		$instance = new QueryComparator( $comparatorList, false );

		$this->assertEquals(
			$expected,
			$instance->getComparatorFromString( $stringComparator )
		);
	}

	/**
	 * @dataProvider stringComparatorProvider
	 */
	public function testGetStringForComparator( $stringComparator, $comparator ) {

		$comparatorList = '';

		$instance = new QueryComparator( $comparatorList, false );

		$this->assertEquals(
			$stringComparator,
			$instance->getStringForComparator( $comparator )
		);
	}

	/**
	 * @dataProvider extractStringComparatorProvider
	 */
	public function testExtractComparatorFromString( $string, $expectedString, $expectedComparator ) {

		$comparatorList = '';

		$instance = new QueryComparator( $comparatorList, true );

		$this->assertEquals(
			$expectedComparator,
			$instance->extractComparatorFromString( $string )
		);

		$this->assertEquals(
			$expectedString,
			$string
		);
	}

	public function stringComparatorProvider() {

		$provider[] = array(
			'!~',
			SMW_CMP_NLKE
		);

		return $provider;
	}

	public function extractStringComparatorProvider() {

		$provider[] = array(
			'!~Foo',
			'Foo',
			SMW_CMP_NLKE
		);

		$provider[] = array(
			'<Foo',
			'Foo',
			SMW_CMP_LESS
		);

		return $provider;
	}

}
