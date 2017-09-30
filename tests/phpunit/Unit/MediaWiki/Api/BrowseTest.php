<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Browse;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Browse
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class BrowseTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new Browse(
			$this->apiFactory->newApiMain( array() ),
			'smwbrowse'
		);

		$this->assertInstanceOf(
			Browse::class,
			$instance
		);
	}

	/**
	 * @dataProvider browseIdProvider
	 */
	public function testExecute( $id ) {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getSQLOptions' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->testEnvironment->registerObject( 'Cache', $cache );
		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new Browse(
			$this->apiFactory->newApiMain(
				[
					'action'   => 'smwbrowse',
					'browse'   => $id,
					'params'   => json_encode( [ 'search' => 'Foo' ] )
				]
			),
			'smwbrowse'
		);

		$instance->execute();
	}

	public function browseIdProvider() {

		$provider[] = [
			'property'
		];

		$provider[] = [
			'category'
		];

		$provider[] = [
			'concept'
		];

		$provider[] = [
			'article'
		];

		return $provider;
	}

}
