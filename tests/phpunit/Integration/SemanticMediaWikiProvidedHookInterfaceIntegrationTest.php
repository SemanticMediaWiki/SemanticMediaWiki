<?php

namespace SMW\Tests\Integration;

use SMW\Tests\Utils\UtilityFactory;
use SMW\ApplicationFactory;
use SMW\ConnectionManager;
use SMW\DIWikiPage;

use RuntimeException;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SemanticMediaWikiProvidedHookInterfaceIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();

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
	public function testRegisteredStoreBeforeQueryResultLookupCompleteHookToPreFetchQueryResult( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'fetchQueryResult' );

		$this->mwHooksHandler->register( 'SMW::Store::BeforeQueryResultLookupComplete', function( $store, $query, &$queryResult ) {
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
	public function testRegisteredStoreBeforeQueryResultLookupCompleteHookToSuppressDefaultQueryResultFetch( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( array( 'fetchQueryResult' ) )
			->getMock();

		$store->expects( $this->never() )
			->method( 'fetchQueryResult' );

		$this->mwHooksHandler->register( 'SMW::Store::BeforeQueryResultLookupComplete', function( $store, $query, &$queryResult ) {

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
	public function testRegisteredStoreAfterQueryResultLookupComplete( $storeClass ) {

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

		$this->mwHooksHandler->register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, &$queryResult ) {

			if ( !$queryResult instanceOf \SMWQueryResult ) {
				throw new RuntimeException( 'Expected a SMWQueryResult instance' );
			}

			return true;
		} );

		$store->getQueryResult( $query );
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testRegisteredFactboxBeforeContentGenerationToSupressDefaultTableCreation( $storeClass ) {

		$this->applicationFactory->getSettings()->set( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_MAIN ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->mwHooksHandler->register( 'SMW::Factbox::BeforeContentGeneration', function( &$text, $semanticData ) {
			$text = $semanticData->getSubject()->getTitle()->getText();
			return false;
		} );

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( new \ParserOutput() ) );

		$parserData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN ) ) );

		$contextSource = $this->getMockBuilder( '\IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->applicationFactory->newFactboxBuilder()->newFactbox( $parserData, $contextSource );
		$instance->doBuild();

		$this->assertEquals(
			'Bar',
			$instance->getContent()
		);
	}

	public function storeClassProvider() {

		$provider[] = array( '\SMWSQLStore3' );
		$provider[] = array( '\SMW\SPARQLStore\SPARQLStore' );

		return $provider;
	}

}
