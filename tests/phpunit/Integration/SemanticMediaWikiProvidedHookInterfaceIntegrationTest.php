<?php

namespace SMW\Tests\Integration;

use SMW\Tests\Util\UtilityFactory;

use RuntimeException;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SemanticMediaWikiProvidedHookInterfaceIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testUnregisteredQueryResultHook( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'fetchQueryResult' );

		$store->getQueryResult( $query );
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testRegisteredSMWStoreSelectQueryResultBeforeHookThatIncludesFetchingOfQueryResult( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'fetchQueryResult' );

		$this->mwHooksHandler->register( 'SMW::Store::selectQueryResultBefore', function( $store, $query, &$queryResult ) {
			$queryResult = 'Foo';
			return true;
		} );

		$this->assertNotEquals(
			'Foo',
			$store->getQueryResult( $query )
		);
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testRegisteredSMWStoreSelectQueryResultBeforeHookToSuppressDefaultQueryResultFetch( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->never() )
			->method( 'fetchQueryResult' );

		$this->mwHooksHandler->register( 'SMW::Store::selectQueryResultBefore', function( $store, $query, &$queryResult ) {

			$queryResult = 'Foo';

			// Return false to suppress additional calls to fetchQueryResult
			return false;
		} );

		$this->assertEquals(
			'Foo',
			$store->getQueryResult( $query )
		);
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testRegisteredSMWStoreSelectQueryResultAfterHook( $storeClass ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'fetchQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$this->mwHooksHandler->register( 'SMW::Store::selectQueryResultAfter', function( $store, &$queryResult ) {

			if ( !$queryResult instanceOf \SMWQueryResult ) {
				throw new RuntimeException( 'Expected a SMWQueryResult instance' );
			}

			return true;
		} );

		$store->getQueryResult( $query );
	}

	public function storeClassProvider() {

		$provider[] = array( '\SMWSQLStore3' );
		$provider[] = array( '\SMW\SPARQLStore\SPARQLStore' );

		return $provider;
	}

}
