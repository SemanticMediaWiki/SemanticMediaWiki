<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\IndicatorProvider;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Indexer\IndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $entityCache;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IndicatorProvider::class,
			new IndicatorProvider( $this->store, $this->entityCache )
		);
	}

	public function testCheckReplicationIndicator() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$instance = new IndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->canCheckReplication( true );

		$options = [
			'action' => 'foo',
			'diff' => null
		];

		$this->assertTrue(
			$instance->hasIndicator( $title, $options )
		);

		$this->assertArrayHasKey(
			'smw-es-replication',
			$instance->getIndicators()
		);
	}

	public function testNoCheckReplicationOnNonExistingTitle() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$instance = new IndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$instance->canCheckReplication( true );

		$this->assertFalse(
			$instance->hasIndicator( $title, [] )
		);

		$this->assertEmpty(
			$instance->getIndicators()
		);
	}

}
