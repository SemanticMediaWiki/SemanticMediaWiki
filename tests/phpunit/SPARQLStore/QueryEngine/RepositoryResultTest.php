<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Exporter\Element\ExpLiteral;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\RepositoryResult
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryResultTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\RepositoryResult',
			new RepositoryResult()
		);

		$this->assertInstanceOf(
			'\Iterator',
			new RepositoryResult()
		);
	}

	public function testIsBooleanTrue() {
		$instance = new RepositoryResult(
			[],
			[ [ new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' ) ] ]
		);

		$this->assertSame( 1, $instance->numRows() );
		$this->assertTrue( $instance->isBooleanTrue() );
	}

	public function testIsBooleanNotTrue() {
		$instance = new RepositoryResult();

		$this->assertFalse(
			$instance->isBooleanTrue()
		);
	}

	public function testGetNumericValue() {
		$instance = new RepositoryResult(
			[],
			[ [ new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' ) ] ]
		);

		$this->assertSame( 1, $instance->numRows() );
		$this->assertSame( 2, $instance->getNumericValue() );
	}

	public function testGetZeroNumericValue() {
		$instance = new RepositoryResult();

		$this->assertSame(
			0,
			$instance->getNumericValue()
		);
	}

	public function testSetGetErrorCode() {
		$instance = new RepositoryResult();

		$this->assertEquals(
			RepositoryResult::ERROR_NOERROR,
			$instance->getErrorCode()
		);

		$instance->setErrorCode(
			RepositoryResult::ERROR_INCOMPLETE
		);

		$this->assertEquals(
			RepositoryResult::ERROR_INCOMPLETE,
			$instance->getErrorCode()
		);
	}

	public function testIteration() {
		$rawList = [
			[
				new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' ),
				new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' )
			],
			[
				new ExpLiteral( '2', 'http://www.w3.org/2001/XMLSchema#integer' )
			]
		];

		$instance = new RepositoryResult(
			[],
			[ $rawList[0], $rawList[1] ]
		);

		foreach ( $instance as $key => $listItem ) {
			$this->assertEquals( $rawList[$key], $listItem );
		}
	}

	public function testGetComments() {
		$instance = new RepositoryResult(
			[],
			[],
			[ 'Foo' ]
		);

		$this->assertContains(
			'Foo',
			$instance->getComments()
		);
	}

}
