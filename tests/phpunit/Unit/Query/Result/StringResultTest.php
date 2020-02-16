<?php

namespace SMW\Tests\Query\Result;

use SMW\Query\Result\StringResult;

/**
 * @covers \SMW\Query\Result\StringResult
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class StringResultTest extends \PHPUnit_Framework_TestCase {

	private $query;

	protected function setUp() : void {

		$this->query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			StringResult::class,
			new StringResult( '', $this->query )
		);
	}

	public function testGetResult() {

		$instance = new StringResult( 'Foobar', $this->query );

		$this->assertEquals(
			'Foobar',
			$instance->getResults()
		);
	}

	public function testGetCount() {

		$instance = new StringResult( 'Foobar', $this->query );
		$instance->setCount( 42 );

		$this->assertEquals(
			42,
			$instance->getCount()
		);
	}

	public function testHasFurtherResults() {

		$instance = new StringResult( 'Foobar', $this->query, true );

		$this->assertTrue(
			$instance->hasFurtherResults()
		);
	}

	public function testGetResult_PreOutputCallback() {

		$instance = new StringResult( 'Foobar', $this->query );

		$instance->setPreOutputCallback( function( $result, $options ) {
			return $result . ' Foo bar';
		} );

		$this->assertEquals(
			'Foobar Foo bar',
			$instance->getResults()
		);
	}

}
