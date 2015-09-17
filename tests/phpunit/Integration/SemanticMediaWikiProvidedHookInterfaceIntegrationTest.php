<?php

namespace SMW\Tests\Integration;

use SMW\Tests\Utils\UtilityFactory;
use SMW\ApplicationFactory;
use SMW\ConnectionManager;
use SMW\DIWikiPage;

use RuntimeException;

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
			->setMethods( array( 'fetchQueryResult' ) )
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
	public function testRegisteredFactboxBeforeContentGenerationToSupressDefaultTableCreation( $storeClass ) {

		$this->applicationFactory->getSettings()->set( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

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
			->setMethods( array( 'getSemanticData', 'getConnection' ) )
			->getMock();

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

		$instance = $this->applicationFactory->newFactboxFactory()->newFactbox( $parserData, $contextSource );
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

		$idGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( array( 'getObjectIds', 'getPropertyTables' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

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

		$idGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'getListOfIdMatchesFor' )
			->will( $this->returnValue( array( 42 ) ) );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( array( 'getPropertyTables', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

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

		$idGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$idGenerator->expects( $this->any() )
			->method( 'getListOfIdMatchesFor' )
			->will( $this->returnValue( array( 42 ) ) );

		$store = $this->getMockBuilder( $storeClass )
			->setMethods( array( 'getPropertyTables', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idGenerator ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

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
			->will( $this->returnValue( array() ) );

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$inTextAnnotationParser = $this->getMockBuilder( '\SMW\InTextAnnotationParser' )
			->setConstructorArgs( array( $parserData, $magicWordsFinder, $redirectTargetFinder ) )
			->setMethods( null )
			->getMock();

		$this->mwHooksHandler->register( 'SMW::Parser::BeforeMagicWordsFinder', function( &$magicWords ) {
			$magicWords = array( 'Foo' );

			// Just to make MW 1.19 happy, otherwise it is not really needed
			return true;
		} );

		$text = '';

		$inTextAnnotationParser->parse( $text );
	}

	public function testRegisteredAddCustomFixedPropertyTables() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( null )
			->getMock();

		$this->mwHooksHandler->register( 'SMW::SQLStore::AddCustomFixedPropertyTables', function( &$customFixedProperties ) {
			$customFixedProperties['Foo'] = '_Bar';

			return true;
		} );

		$this->assertEquals(
			'smw_fpt_bar',
			$store->findPropertyTableID( new \SMW\DIProperty( 'Foo' ) )
		);
	}

	public function testRegisteredAfterDataUpdateComplete() {

		$test = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'is' ) )
			->getMock();

		$test->expects( $this->once() )
			->method( 'is' )
			->with( $this->equalTo( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( null )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$this->mwHooksHandler->register( 'SMW::SQLStore::AfterDataUpdateComplete', function( $store, $semanticData, $compositePropertyTableDiffIterator ) use ( $test ){
			$test->is( $compositePropertyTableDiffIterator->getCombinedIdListOfChangedEntities() );

			return true;
		} );

		$store->updateData(
			new \SMW\SemanticData( DIWikiPage::newFromText( 'Foo' ) )
		);
	}

	public function storeClassProvider() {

		$provider[] = array( '\SMWSQLStore3' );
		$provider[] = array( '\SMW\SPARQLStore\SPARQLStore' );

		return $provider;
	}

}
