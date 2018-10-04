<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIProperty;
use SMW\SPARQLStore\ReplicationDataTruncator;

/**
 * @covers \SMW\SPARQLStore\ReplicationDataTruncator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReplicationDataTruncatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticData;

	public function setUp() {

		$this->semanticData = $this->getMockBuilder( '\SMW\semanticData' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\ReplicationDataTruncator',
			new ReplicationDataTruncator()
		);
	}

	public function testOnEmptyList() {

		$instance = new ReplicationDataTruncator();
		$semanticData = $instance->doTruncate( $this->semanticData );

		$this->assertSame(
			$this->semanticData,
			$semanticData
		);
	}

	public function testOnExemptedList() {

		$property = new DIProperty( 'Foo_bar' );

		$this->semanticData->expects( $this->once() )
			->method( 'removeProperty' )
			->with( $this->equalTo( $property ) );

		$instance = new ReplicationDataTruncator();
		$instance->setPropertyExemptionList( [ 'Foo bar' ] );

		$instance->doTruncate( $this->semanticData );
	}

	public function testOnExemptedListWithPredefinedProperty() {

		$property = new DIProperty( '_ASK' );

		$this->semanticData->expects( $this->once() )
			->method( 'removeProperty' )
			->with($this->equalTo( $property ) );

		$instance = new ReplicationDataTruncator();
		$instance->setPropertyExemptionList( [ 'Has query' ] );

		$instance->doTruncate( $this->semanticData );
	}

}
