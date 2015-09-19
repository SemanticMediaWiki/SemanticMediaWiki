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

	public function stringComparatorProvider() {

		$provider[] = array(
			'!~',
			SMW_CMP_NLKE
		);

		return $provider;
	}

}
