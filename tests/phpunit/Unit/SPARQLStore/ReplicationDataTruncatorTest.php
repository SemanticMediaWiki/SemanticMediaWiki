<?php

namespace SMW\Tests\Unit\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;
use SMW\SPARQLStore\ReplicationDataTruncator;

/**
 * @covers \SMW\SPARQLStore\ReplicationDataTruncator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ReplicationDataTruncatorTest extends TestCase {

	private $semanticData;

	public function setUp(): void {
		$this->semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ReplicationDataTruncator::class,
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
		$property = new Property( 'Foo_bar' );

		$this->semanticData->expects( $this->once() )
			->method( 'removeProperty' )
			->with( $property );

		$instance = new ReplicationDataTruncator();
		$instance->setPropertyExemptionList( [ 'Foo bar' ] );

		$instance->doTruncate( $this->semanticData );
	}

	public function testOnExemptedListWithPredefinedProperty() {
		$property = new Property( '_ASK' );

		$this->semanticData->expects( $this->once() )
			->method( 'removeProperty' )
			->with( $property );

		$instance = new ReplicationDataTruncator();
		$instance->setPropertyExemptionList( [ 'Has query' ] );

		$instance->doTruncate( $this->semanticData );
	}

}
