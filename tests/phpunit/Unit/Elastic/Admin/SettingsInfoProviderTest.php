<?php

namespace SMW\Tests\Elastic\Admin;

use SMW\Elastic\Admin\SettingsInfoProvider;
use SMW\Elastic\Connection\DummyClient;

/**
 * @covers \SMW\Elastic\Admin\SettingsInfoProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SettingsInfoProviderTest extends \PHPUnit_Framework_TestCase {

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp() {

		$this->outputFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Specials\Admin\OutputFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->webRequest = $this->getMockBuilder( '\WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( new DummyClient() ) );
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

		$this->assertInternalType(
			'string',
			$instance->getHtml()
		);
	}

	public function testHandleRequest() {

		$this->outputFormatter->expects( $this->once() )
			->method( 'addParentLink' )
			->with(	$this->equalTo( [ 'action' => 'elastic' ] ) );

		$instance = new SettingsInfoProvider(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
