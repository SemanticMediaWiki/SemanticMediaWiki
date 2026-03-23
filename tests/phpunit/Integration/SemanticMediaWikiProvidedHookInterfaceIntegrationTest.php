<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\LinksProcessor;
use SMW\ParserData;
use SMW\Query\QueryResult;
use SMW\Services\Exception\ServiceNotFoundException;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\RedirectUpdater;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\SQLStoreUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SemanticMediaWikiProvidedHookInterfaceIntegrationTest extends TestCase {

	private $testEnvironment;
	private $mwHooksHandler;
	private $applicationFactory;
	private $spyLogger;

	protected function setUp(): void {
		parent::setUp();

		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( [ $this, 'mockConnection' ] );

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->mwHooksHandler = $this->testEnvironment->getUtilityFactory()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->testEnvironment->registerObject( 'ConnectionManager', $connectionManager );
		$this->testEnvironment->registerObject( 'JobFactory', $jobFactory );

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function mockConnection( $id ) {
		if ( $id === 'sparql' ) {
			$client = $this->getMockBuilder( RepositoryClient::class )
				->disableOriginalConstructor()
				->getMock();

			$connection = $this->getMockBuilder( RepositoryConnection::class )
				->disableOriginalConstructor()
				->getMock();

			$connection->expects( $this->any() )
				->method( 'getRepositoryClient' )
				->willReturn( $client );

		} else {
			$connection = $this->getMockBuilder( Database::class )
				->disableOriginalConstructor()
				->getMock();

			$connection->expects( $this->any() )
				->method( 'select' )
				->willReturn( [] );

			$connection->expects( $this->any() )
				->method( 'selectRow' )
				->willReturn( false );
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

		$this->mwHooksHandler->register( 'SMW::Store::BeforeQueryResultLookupComplete', static function ( $store, $query, &$queryResult ) {
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

		$this->mwHooksHandler->register( 'SMW::Store::BeforeQueryResultLookupComplete', static function ( $store, $query, &$queryResult ) {
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
		$queryResult = $this->getMockBuilder( QueryResult::class )
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
			->willReturn( $queryResult );

		$this->mwHooksHandler->register( 'SMW::Store::AfterQueryResultLookupComplete', static function ( $store, &$queryResult ) {
			if ( !$queryResult instanceof QueryResult ) {
				throw new RuntimeException( 'Expected a QueryResult instance' );
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( new WikiPage( 'Bar', NS_MAIN ) );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( [ 'getSemanticData', 'getConnection', 'service' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'service' )
			->willThrowException( new ServiceNotFoundException( 'foo' ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->once() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->applicationFactory->registerObject( 'Store', $store );

		$this->mwHooksHandler->register( 'SMW::Factbox::BeforeContentGeneration', static function ( &$text, $semanticData ) {
			$text = $semanticData->getSubject()->getTitle()->getText();
			return false;
		} );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->once() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$instance = $factboxFactory->newFactbox(
			$title,
			new ParserOutput()
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
		$storeClass = SQLStore::class;

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$store = $this->getMockBuilder( $storeClass )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getPropertyTables', 'getConnection' ] )
			->getMock();

		$redirectUpdater = $this->getMockBuilder( RedirectUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$factory = $this->getMockBuilder( SQLStoreFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticDataLookup = $this->getMockBuilder( CachingSemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$factory->expects( $this->any() )
			->method( 'newRedirectUpdater' )
			->willReturn( $redirectUpdater );

		$factory->expects( $this->any() )
			->method( 'newSemanticDataLookup' )
			->willReturn( $semanticDataLookup );

		$factory->expects( $this->any() )
			->method( 'newUpdater' )
			->willReturn( new SQLStoreUpdater( $store, $factory ) );

		$idGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$store->setFactory( $factory );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idGenerator );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->mockConnection( 'mw.db' ) );

		$null = 0;

		$reachedTheBeforeChangeTitleCompleteHook = false;
		$this->mwHooksHandler->register( 'SMW::SQLStore::BeforeChangeTitleComplete', static function () use ( &$reachedTheBeforeChangeTitleCompleteHook ) {
			$reachedTheBeforeChangeTitleCompleteHook = true;
		} );

		$store->changeTitle( $title, $title, $null, $null );

		$this->assertTrue( $reachedTheBeforeChangeTitleCompleteHook );
	}

	public function testRegisteredSQLStoreBeforeDeleteSubjectComplete() {
		// To make this work with SPARQLStore, need to inject the basestore
		$storeClass = SQLStore::class;

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$idGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [ 42 ] );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( [ 'getPropertyTables', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idGenerator );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$reachedTheBeforeDeleteSubjectCompleteHook = false;
		$this->mwHooksHandler->register( 'SMW::SQLStore::BeforeDeleteSubjectComplete', static function () use ( &$reachedTheBeforeDeleteSubjectCompleteHook ) {
			$reachedTheBeforeDeleteSubjectCompleteHook = true;
		} );

		$store->deleteSubject( $title );

		$this->assertTrue(
			$reachedTheBeforeDeleteSubjectCompleteHook
		);
	}

	public function testRegisteredSQLStoreAfterDeleteSubjectComplete() {
		// To make this work with SPARQLStore, need to inject the basestore
		$storeClass = SQLStore::class;

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$idGenerator = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'findIdsByTitle' )
			->willReturn( [ 42 ] );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( [ 'getPropertyTables', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idGenerator );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$reachedTheAfterDeleteSubjectCompleteHook = false;
		$this->mwHooksHandler->register( 'SMW::SQLStore::AfterDeleteSubjectComplete', static function () use ( &$reachedTheAfterDeleteSubjectCompleteHook ) {
			$reachedTheAfterDeleteSubjectCompleteHook = true;
		} );

		$store->deleteSubject( $title );

		$this->assertTrue( $reachedTheAfterDeleteSubjectCompleteHook );
	}

	public function testRegisteredParserBeforeMagicWordsFinder() {
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromTitle( $title ) );

		$parserData->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$parserData->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$magicWordsFinder = $this->getMockBuilder( MagicWordsFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$magicWordsFinder->expects( $this->once() )
			->method( 'findMagicWordInText' )
			->with(
				'Foo',
				$this->anything() )
			->willReturn( [] );

		$linksProcessor = $this->getMockBuilder( LinksProcessor::class )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( RedirectTargetFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$inTextAnnotationParser = $this->getMockBuilder( InTextAnnotationParser::class )
			->setConstructorArgs( [ $parserData, $linksProcessor, $magicWordsFinder, $redirectTargetFinder ] )
			->setMethods( null )
			->getMock();

		$hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$hookDispatcher->expects( $this->once() )
			->method( 'onBeforeMagicWordsFinder' )
			->willReturnCallback( static function ( &$magicWords ) {
				$magicWords = [ 'Foo' ];
			} );

		$inTextAnnotationParser->setHookDispatcher(
			$hookDispatcher
		);

		$text = '';

		$inTextAnnotationParser->parse( $text );
	}

	public function testRegisteredAddCustomFixedPropertyTables() {
		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( null )
			->getMock();

		$this->mwHooksHandler->register( 'SMW::SQLStore::AddCustomFixedPropertyTables', static function ( &$customFixedProperties, &$fixedPropertyTablePrefix ) {
			// Standard table prefix
			$customFixedProperties['Foo'] = '_Bar';

			// Custom table prefix
			$customFixedProperties['Foobar'] = '_Foooo';
			$fixedPropertyTablePrefix['Foobar'] = 'smw_ext';

			return true;
		} );

		$this->assertEquals(
			'smw_fpt_bar',
			$store->findPropertyTableID( new Property( 'Foo' ) )
		);

		$this->assertEquals(
			'smw_ext_foooo',
			$store->findPropertyTableID( new Property( 'Foobar' ) )
		);
	}

	public function testRegisteredAfterDataUpdateComplete() {
		$test = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'is' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'is' )
			->with( [] );

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->setOption( 'smwgAutoRefreshSubject', true );

		$store->setLogger( $this->spyLogger );

		$this->mwHooksHandler->register( 'SMW::SQLStore::AfterDataUpdateComplete', static function ( $store, $semanticData, $changeOp ) use ( $test ){
			$test->is( $changeOp->getChangedEntityIdSummaryList() );

			return true;
		} );

		$store->updateData(
			new SemanticData( WikiPage::newFromText( 'Foo' ) )
		);
	}

	public function storeClassProvider() {
		$provider[] = [ SQLStore::class ];
		$provider[] = [ SPARQLStore::class ];

		return $provider;
	}

}
