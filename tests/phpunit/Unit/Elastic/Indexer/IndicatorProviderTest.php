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

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IndicatorProvider::class,
			new IndicatorProvider( $this->store )
		);
	}

	public function testCheckReplicatioIndicator() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$instance = new IndicatorProvider(
			$this->store
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

}
