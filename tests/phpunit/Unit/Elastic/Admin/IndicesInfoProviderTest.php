<?php

namespace SMW\Tests\Unit\Elastic\Admin;

use MediaWiki\Request\WebRequest;
use PHPUnit\Framework\TestCase;
use SMW\Elastic\Admin\IndicesInfoProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Store;

/**
 * @covers \SMW\Elastic\Admin\IndicesInfoProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class IndicesInfoProviderTest extends TestCase {

	private $outputFormatter;
	private $webRequest;
	private $store;

	protected function setUp(): void {
		$this->outputFormatter = $this->getMockBuilder( OutputFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->outputFormatter->expects( $this->any() )
			->method( 'encodeAsJson' )
			->willReturn( '' );

		$this->webRequest = $this->getMockBuilder( WebRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( new DummyClient() );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IndicesInfoProvider::class,
			new IndicesInfoProvider( $this->outputFormatter )
		);
	}

	public function testGetTask() {
		$instance = new IndicesInfoProvider(
			$this->outputFormatter
		);

		$this->assertEquals(
			'indices',
			$instance->getSupplementTask()
		);

		$this->assertEquals(
			'elastic/indices',
			$instance->getTask()
		);
	}

	public function testGetHtml() {
		$instance = new IndicesInfoProvider(
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

		$instance = new IndicesInfoProvider(
			$this->outputFormatter
		);

		$instance->setStore( $this->store );
		$instance->handleRequest( $this->webRequest );
	}

}
