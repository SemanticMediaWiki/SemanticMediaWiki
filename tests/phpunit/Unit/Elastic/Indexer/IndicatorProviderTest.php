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

	public function testGetModules() {

		$instance = new IndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertContains(
			'smw.check.replication',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$instance = new IndicatorProvider(
			$this->store,
			$this->entityCache
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
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

		$res = $instance->getIndicators();

		$this->assertArrayHasKey(
			'smw-es-replication',
			$res
		);

		$this->assertContains(
			'data-subject="Foo#0##"',
			$res['smw-es-replication']
		);
	}

	public function testCheckReplicationIndicatorForPredefinedProperty() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Modification date' ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_PROPERTY ) );

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

		$res = $instance->getIndicators();

		$this->assertArrayHasKey(
			'smw-es-replication',
			$res
		);

		$this->assertContains(
			'data-subject="_MDAT#102##"',
			$res['smw-es-replication']
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
