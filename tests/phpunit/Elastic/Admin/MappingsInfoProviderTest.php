<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\MappingsInfoProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Admin\MappingsInfoProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class MappingsInfoProviderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturn( '' );

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( new DummyClient() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MappingsInfoProvider::class,
			new MappingsInfoProvider( $this->outputFormatter )
		);
	}

	public function testGetTask() {
		$instance = new MappingsInfoProvider(
			$this->outputFormatter
		);

		$this->assertEquals(
			'mappings',
			$instance->getSupplementTask()
		);

		$this->assertEquals(
			'elastic/mappings',
			$instance->getTask()
		);
	}

	public function testGetHtml() {
		$instance = new MappingsInfoProvider(
			$this->outputFormatter
		);

		$this->assertIsString(

			$instance->getHtml()
		);
	}

	public function testHandleRequest() {
		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	[ 'action' => 'elastic' ] );

		$instance = new MappingsInfoProvider(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
