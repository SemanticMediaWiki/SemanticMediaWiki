<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\SettingsInfoProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Admin\SettingsInfoProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SettingsInfoProviderTest extends \PHPUnit\Framework\TestCase {

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
			->onlyMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( new DummyClient() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SettingsInfoProvider::class,
			new SettingsInfoProvider( $this->outputFormatter )
		);
	}

	public function testGetTask() {
		$instance = new SettingsInfoProvider(
			$this->outputFormatter
		);

		$this->assertEquals(
			'settings',
			$instance->getSupplementTask()
		);

		$this->assertEquals(
			'elastic/settings',
			$instance->getTask()
		);
	}

	public function testGetHtml() {
		$instance = new SettingsInfoProvider(
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

		$instance = new SettingsInfoProvider(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
