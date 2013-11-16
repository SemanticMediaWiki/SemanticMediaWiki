<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\OutputPageParserOutput;
use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\ArticlePurge;
use SMW\ParserData;
use SMW\DIProperty;
use SMW\Setup;

use RequestContext;
use WikiPage;
use Title;

/**
 * @covers \SMW\FunctionHook
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiFunctionHookIntegrationTest extends \MediaWikiTestCase {

	/** @var array */
	private $hooks = array();

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @return 1.9
	 */
	protected function setUp() {
		$this->removeFunctionHookRegistrationBeforeTest();
		parent::setUp();

	}

	/**
	 * @return 1.9
	 */
	protected function tearDown() {
		parent::tearDown();
		$this->restoreFuntionHookRegistrationAfterTest();
	}

	/**
	 * In order for the test not being influenced by an exisiting setup
	 * registration we remove the configuration from the GLOBALS temporary
	 * and enable to assign hook definitions freely during testing
	 *
	 * @return 1.9
	 */
	protected function removeFunctionHookRegistrationBeforeTest() {
		$this->hooks = $GLOBALS['wgHooks'];
		$GLOBALS['wgHooks'] = array();
	}

	/**
	 * @return 1.9
	 */
	protected function restoreFuntionHookRegistrationAfterTest() {
		$GLOBALS['wgHooks'] = $this->hooks;
	}

	/**
	 * @since 1.9
	 */
	protected function newExtensionContext() {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgPageSpecialProperties', array( DIProperty::TYPE_MODIFICATION_DATE ) );
		$context->getSettings()->set( 'smwgNamespacesWithSemanticLinks', array( NS_MAIN => true ) );
		$context->getSettings()->set( 'smwgCacheType', 'hash' );
		$context->getSettings()->set( 'smwgAutoRefreshOnPurge', true );

		$mockBuilder = new MockObjectBuilder( new CoreMockObjectRepository() );

		$mockData = $mockBuilder->newObject( 'SemanticData', array(
			'hasVisibleProperties' => false,
		) );

		$mockStore = $mockBuilder->newObject( 'Store', array(
			'getSemanticData' => $mockData,
			'changeTitle'     => array( $this, 'mockStoreOnChangeTitle' )
		) );

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $mockStore );

		return $context;
	}

	/**
	 * @return 1.9
	 */
	protected function runExtensionSetup( $context ) {
		$setup = new Setup( $GLOBALS, $context );
		$setup->run();
	}

	/**
	 * @since 1.9
	 */
	public function testOnArticlePurgeOnDatabase() {

		$context = $this->newExtensionContext();

		$this->runExtensionSetup( $context );

		$wikiPage = new WikiPage( Title::newFromText( __METHOD__ ) );
		$this->editPageAndFetchInfo( $wikiPage, __METHOD__ );

		$wikiPage->doPurge();

		$result = $context->getDependencyBuilder()
			->newObject( 'CacheHandler' )
			->setKey( ArticlePurge::newCacheId( $wikiPage->getTitle()->getArticleID() ) )
			->get();

		$this->assertTrue(
			$result,
			'Asserts that the ArticlePurge hooks was executed and created an entry'
		);

		$this->deletePage( $wikiPage, __METHOD__ );

	}

	/**
	 * @since 1.9
	 */
	public function testOnNewRevisionFromEditCompleteOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$text = '[[Quuy::bar]]';

		$wikiPage = new WikiPage( Title::newFromText( __METHOD__ ) );
		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__, $text );

		$this->assertInstanceOf( 'ParserOutput', $editInfo->output );
		$parserData = new ParserData( $wikiPage->getTitle(), $editInfo->output );

		$expected = array(
			'propertyKey' => array( '_SKEY', '_MDAT', 'Quuy' )
		);

		$this->assertPropertiesAreSet( $parserData->getData(), $expected );

		$this->deletePage( $wikiPage, __METHOD__ );

	}

	/**
	 * @since 1.9
	 */
	public function testOnOutputPageParserOutputeOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$wikiPage = new WikiPage( Title::newFromText( __METHOD__ ) );
		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__ );
		$parserOutput = $editInfo->output;

		$this->assertInstanceOf( 'ParserOutput', $parserOutput );

		$context = new RequestContext();
		$context->setTitle( $wikiPage->getTitle() );
		$context->getOutput()->addParserOutputNoText( $parserOutput );

		$this->deletePage( $wikiPage, __METHOD__ );

	}

	/**
	 * @since 1.9
	 */
	public function testTitleMoveCompleteOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$newTitle = Title::newFromText( __METHOD__ . '-new' );
		$wikiPage = new WikiPage( Title::newFromText( __METHOD__ ) );
		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__ );

		$result = $wikiPage->getTitle()->moveTo( $newTitle, false, 'test', true );

		$this->assertTrue( $result );
		$this->assertTrue( $newTitle->runOnMockStoreChangeTitleMethod );

		$this->deletePage( $wikiPage, __METHOD__ );

	}

	/**
	 * @since 1.9
	 */
	public function mockStoreOnChangeTitle( $oldTitle, $newTitle, $oldId, $newId ) {
		$newTitle->runOnMockStoreChangeTitleMethod = true ;
	}

	/**
	 * @since 1.9
	 */
	protected function editPageAndFetchInfo( WikiPage $wikiPage, $on, $text = 'Foo' ) {

		$user = new MockSuperUser();

		if ( method_exists( 'WikiPage', 'doEditContent' ) ) {

			$content = \ContentHandler::makeContent(
				$text,
				$wikiPage->getTitle(),
				CONTENT_MODEL_WIKITEXT
			);

			$wikiPage->doEditContent( $content, "testing " . $on, EDIT_NEW, false, $user );

			$content = $wikiPage->getRevision()->getContent();
			$format  = $content->getContentHandler()->getDefaultFormat();

			return $wikiPage->prepareContentForEdit( $content, null, $user, $format );

		}

		$wikiPage->doEdit( $text, "testing " . $on, EDIT_NEW, false, $user );

		return $wikiPage->prepareTextForEdit(
			$wikiPage->getRevision()->getRawText(),
			null,
			$user
		);
	}

	/**
	 * @since 1.9
	 */
	protected function deletePage( WikiPage $wikiPage, $on ) {

		if ( $wikiPage->exists() ) {
			$wikiPage->doDeleteArticle( "testing done on " . $on );
		}

	}

	/**
	 * @since  1.9
	 */
	protected function assertPropertiesAreSet( SemanticData $semanticData, array $expected ) {

		foreach ( $semanticData->getProperties() as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			$this->assertContains(
				$property->getKey(),
				$expected['propertyKey'],
				'Asserts that a specific property key is set'
			);

		}
	}

}
