<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\Services\ServicesManager;
use SMW\MediaWiki\Api\BrowseByProperty;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Api\BrowseByProperty
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class BrowseByPropertyTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $legacySpecialLookup;
	private $apiFactory;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->legacySpecialLookup = new \SMW\SQLStore\Lookup\LegacySpecialLookup(
			$this->store
		);

		$servicesManager = new \SMW\Services\ServicesManager();

		$servicesManager->registerCallback( 'special.lookup', function() {
			return $this->legacySpecialLookup;
		} );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->will( $this->returnCallback( $servicesManager->returnCallback() ) );

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->registerObject( 'Store', $this->store );

		$this->apiFactory = UtilityFactory::getInstance()->newMwApiFactory();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new BrowseByProperty(
			$this->apiFactory->newApiMain( array() ),
			'browsebyproperty'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\BrowseByProperty',
			$instance
		);
	}

	public function testExecute() {

		$list[] = array(
			new DIProperty( 'Foo' ),
			42
		);

		$list[] = array(
			new DIProperty( 'Foaf:Foo' ),
			1001
		);

		$list[] = array(
			new DIProperty( 'Unknown:Foo' ),
			1001
		);

		$cachedListLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\CachedListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cachedListLookup->expects( $this->once() )
			->method( 'lookup' )
			->will( $this->returnValue( $list ) );

		$this->legacySpecialLookup->registerCallback( 'special.properties', function( $requestOptions = null ) use( $cachedListLookup ) {
				return $cachedListLookup;
		} );

		$this->applicationFactory->registerObject( 'Store', $this->store );

		$result = $this->apiFactory->doApiRequest( array(
			'action'  => 'browsebyproperty',
			'property' => 'Foo'
		) );

		$this->assertArrayHasKey(
			'query',
			$result
		);

		$this->assertArrayHasKey(
			'version',
			$result
		);

		$this->assertArrayHasKey(
			'query-continue-offset',
			$result
		);

		$this->assertArrayHasKey(
			'meta',
			$result
		);
	}

}
