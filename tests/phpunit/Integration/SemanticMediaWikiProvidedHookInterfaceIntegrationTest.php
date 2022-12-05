<?php

namespace SMW\Tests\Integration;

use RuntimeException;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class SemanticMediaWikiProvidedHookInterfaceIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $applicationFactory;
	private $spyLogger;

	protected function setUp() : void {
		parent::setUp();

		$updateJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\UpdateJob' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->will( $this->returnValue( $updateJob ) );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will($this->returnCallback( [ $this, 'mockConnection' ] ) );

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->mwHooksHandler = $this->testEnvironment->getUtilityFactory()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->testEnvironment->registerObject( 'ConnectionManager', $connectionManager );
		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() : void {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function mockConnection( $id ) {

		if ( $id === 'sparql' ) {
			$client = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryClient' )
				->disableOriginalConstructor()
				->getMock();

			$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
				->disableOriginalConstructor()
				->getMock();

			$connection->expects( $this->any() )
				->method( 'getRepositoryClient' )
				->will( $this->returnValue( $client ) );

		} else {
			$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
				->disableOriginalConstructor()
				->getMock();

			$connection->expects( $this->any() )
				->method( 'select' )
				->will( $this->returnValue( [] ) );

			$connection->expects( $this->any() )
				->method( 'selectRow' )
				->will( $this->returnValue( false ) );
		}

		return $connection;
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testUnregisteredQueryResultHook( $storeClass ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( [ 'fetchQueryResult' ] )
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
			->setMethods( [ 'fetchQueryResult' ] )
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
			->setMethods( [ 'fetchQueryResult' ] )
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
			->setMethods( [ 'fetchQueryResult' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'fetchQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$this->mwHooksHandler->register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, &$queryResult ) {

			if ( !$queryResult instanceof \SMWQueryResult ) {
				throw new RuntimeException( 'Expected a SMWQueryResult instance' );
			}

			return true;
		} );

		$store->getQueryResult( $query );
	}

	/**
	 * @dataProvider storeClassProvider
	 */
	public function testRegisteredFactboxBeforeContentGenerationToSuppressDefaultTableCreation( $storeClass ) {

		$factboxFactory = $this->applicationFactory->singleton( 'FactboxFactory' );

		$checkMagicWords = $factboxFactory->newCheckMagicWords(
			[
				'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
				'showFactbox' => SMW_FACTBOX_NONEMPTY
			]
		);

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_MAIN ) ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( true ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [] ) );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( [ 'getSemanticData', 'getConnection', 'service' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'service' )
			->will($this->throwException(new \SMW\Services\Exception\ServiceNotFoundException( 'foo' ) ));

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->once() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->mwHooksHandler->register( 'SMW::Factbox::BeforeContentGeneration', function( &$text, $semanticData ) {
			$text = $semanticData->getSubject()->getTitle()->getText();
			return false;
		} );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue(  NS_MAIN ) );

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = $factboxFactory->newFactbox(
			$title,
			new \ParserOutput()
		);

		$instance->setCheckMagicWords(
			$checkMagicWords
		);

		$instance->doBuild();

		$this->assertEquals(
			'Bar',
			$instance->getContent()
		);
	}

	public function testRegisteredSQLStoreBeforeChangeTitleComplete() {

		// To make this work with SPARQLStore, need to inject the basestore
		$storeClass = '\SMWSQLStore3';

		$title = \Title::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getPropertyTables', 'getConnection' ] )
			->getMock();

		$redirectUpdater = $this->getMockBuilder( '\SMW\SQLStore\RedirectUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$factory = $this->getMockBuilder( '\SMW\SQLStore\SQLStoreFactory' )
			->disableOriginalConstructor()
			->getMock();

		$semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newRedirectUpdater' )
			->will( $this->returnValue( $redirectUpdater ) );

		$factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->will( $this->returnValue( $semanticDataLookup ) );

		$factory->expects( $this->any() )
			->method( 'newUpdater' )
			->will( $this->returnValue( new \SMW\SQLStore\SQLStoreUpdater( $store, $factory ) ) );

		$idGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$store->setFactory( $factory );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->mockConnection( 'mw.db' ) ) );


		$null = 0;

		$this->mwHooksHandler->register( 'SMW::SQLStore::BeforeChangeTitleComplete', function( $store, $oldTitle, $newTitle, $pageId, $redirectId ) {
			return $store->reachedTheBeforeChangeTitleCompleteHook = true;
		} );

		$store->changeTitle( $title, $title, $null, $null );

		$this->assertTrue(
			$store->reachedTheBeforeChangeTitleCompleteHook
		);
	}

	public function testRegisteredSQLStoreBeforeDeleteSubjectComplete() {

		// To make this work with SPARQLStore, need to inject the basestore
		$storeClass = '\SMWSQLStore3';

		$title = \Title::newFromText( __METHOD__ );

		$idGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->will( $this->returnValue( [ 42 ] ) );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( [ 'getPropertyTables', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->mwHooksHandler->register( 'SMW::SQLStore::BeforeDeleteSubjectComplete', function( $store, $title ) {
			return $store->reachedTheBeforeDeleteSubjectCompleteHook = true;
		} );

		$store->deleteSubject( $title );

		$this->assertTrue(
			$store->reachedTheBeforeDeleteSubjectCompleteHook
		);
	}

	public function testRegisteredSQLStoreAfterDeleteSubjectComplete() {

		// To make this work with SPARQLStore, need to inject the basestore
		$storeClass = '\SMWSQLStore3';

		$title = \Title::newFromText( __METHOD__ );

		$idGenerator = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->will( $this->returnValue( [ 42 ] ) );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( [ 'getPropertyTables', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$this->mwHooksHandler->register( 'SMW::SQLStore::AfterDeleteSubjectComplete', function( $store, $title ) {
			return $store->reachedTheAfterDeleteSubjectCompleteHook = true;
		} );

		$store->deleteSubject( $title );

		$this->assertTrue(
			$store->reachedTheAfterDeleteSubjectCompleteHook
		);
	}

	public function testRegisteredParserBeforeMagicWordsFinder() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$title = \Title::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromTitle( $title ) ) );

		$parserData->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$parserData->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$magicWordsFinder = $this->getMockBuilder( '\SMW\MediaWiki\MagicWordsFinder' )
			->disableOriginalConstructor()
			->getMock();

		$magicWordsFinder->expects( $this->once() )
			->method( 'findMagicWordInText' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->anything() )
			->will( $this->returnValue( [] ) );

		$linksProcessor = $this->getMockBuilder( '\SMW\Parser\LinksProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$inTextAnnotationParser = $this->getMockBuilder( '\SMW\Parser\InTextAnnotationParser' )
			->setConstructorArgs( [ $parserData, $linksProcessor, $magicWordsFinder, $redirectTargetFinder ] )
			->setMethods( null )
			->getMock();

		$hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$hookDispatcher->expects( $this->once() )
			->method( 'onBeforeMagicWordsFinder' )
			->will($this->returnCallback( function( &$magicWords ) {
				$magicWords = [ 'Foo' ];
			} ) );

		$inTextAnnotationParser->setHookDispatcher(
			$hookDispatcher
		);

		$text = '';

		$inTextAnnotationParser->parse( $text );
	}

	public function testRegisteredAddCustomFixedPropertyTables() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( null )
			->getMock();

		$this->mwHooksHandler->register( 'SMW::SQLStore::AddCustomFixedPropertyTables', function( &$customFixedProperties, &$fixedPropertyTablePrefix ) {

			// Standard table prefix
			$customFixedProperties['Foo'] = '_Bar';

			// Custom table prefix
			$customFixedProperties['Foobar'] = '_Foooo';
			$fixedPropertyTablePrefix['Foobar'] = 'smw_ext';

			return true;
		} );

		$this->assertEquals(
			'smw_fpt_bar',
			$store->findPropertyTableID( new \SMW\DIProperty( 'Foo' ) )
		);

		$this->assertEquals(
			'smw_ext_foooo',
			$store->findPropertyTableID( new \SMW\DIProperty( 'Foobar' ) )
		);
	}

	public function testRegisteredAfterDataUpdateComplete() {

		$test = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'is' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'is' )
			->with( $this->equalTo( [] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$store->setOption( 'smwgAutoRefreshSubject', true );

		$store->setLogger( $this->spyLogger );

		$this->mwHooksHandler->register( 'SMW::SQLStore::AfterDataUpdateComplete', function( $store, $semanticData, $changeOp ) use ( $test ){
			$test->is( $changeOp->getChangedEntityIdSummaryList() );

			return true;
		} );

		$store->updateData(
			new \SMW\SemanticData( DIWikiPage::newFromText( 'Foo' ) )
		);
	}

	public function storeClassProvider() {

		$provider[] = [ '\SMWSQLStore3' ];
		$provider[] = [ '\SMW\SPARQLStore\SPARQLStore' ];

		return $provider;
	}

}
