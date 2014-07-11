<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\FederateResultList;

use SMWExpLiteral as ExpLiteral;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\FederateResultList
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FederateResultListTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\FederateResultList',
			new FederateResultList( array(), array() )
		);

		$this->assertInstanceOf(
			'\Iterator',
			new FederateResultList( array(), array() )
		);
	}

	public function testIsBooleanTrue() {

		$instance = new FederateResultList(
			array(),
			array( array( new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' ) ) )
		);

		$this->assertEquals( 1, $instance->numRows() );
		$this->assertTrue( $instance->isBooleanTrue() );
	}

	public function testIsBooleanNotTrue() {

		$instance = new FederateResultList(
			array(),
			array()
		);

		$this->assertFalse( $instance->isBooleanTrue() );
	}

	public function testGetNumericValue() {

		$instance = new FederateResultList(
			array(),
			array( array( new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' ) ) )
		);

		$this->assertEquals( 1, $instance->numRows() );
		$this->assertSame( 2, $instance->getNumericValue() );
	}

	public function testGetZeroNumericValue() {

		$instance = new FederateResultList(
			array(),
			array()
		);

		$this->assertSame( 0, $instance->getNumericValue() );
	}

	public function testSetGetErrorCode() {

		$instance = new FederateResultList(
			array(),
			array()
		);

		$instance->setErrorCode( FederateResultList::ERROR_INCOMPLETE );

		$this->assertEquals(
			FederateResultList::ERROR_INCOMPLETE,
			$instance->getErrorCode()
		);
	}

	public function testIteration() {

		$rawList = array(
			array(
				new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' ),
				new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' )
			),
			array(
				new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' )
			)
		);

		$instance = new FederateResultList(
			array(),
			array( $rawList[0], $rawList[1] )
		);

		foreach ( $instance as $key => $listItem ) {
			$this->assertEquals( $rawList[ $key ], $listItem );
		}
	}

	public function testGetComments() {

		$instance = new FederateResultList(
			array(),
			array(),
			array( 'Foo' )
		);

		$this->assertContains( 'Foo', $instance->getComments() );
	}

}

