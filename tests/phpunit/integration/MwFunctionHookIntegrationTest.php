<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\OutputPageParserOutput;
use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\ArticlePurge;
use SMW\ParserData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\StoreFactory;

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
class MwFunctionHookIntegrationTest extends MwIntegrationTestCase {

	protected function newExtensionContext() {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgPageSpecialProperties', array( DIProperty::TYPE_MODIFICATION_DATE ) );
		$context->getSettings()->set( 'smwgNamespacesWithSemanticLinks', array( NS_MAIN => true ) );
		$context->getSettings()->set( 'smwgCacheType', 'hash' );
		$context->getSettings()->set( 'smwgAutoRefreshOnPurge', true );

		$mockBuilder = new MockObjectBuilder( new CoreMockObjectRepository() );

		$mockData = $mockBuilder->newObject( 'SemanticData', array(
			'hasVisibleProperties' => false,
			'getSubject'           => $mockBuilder->newObject( 'DIWikiPage' ),
			'getProperties'        => array(),
			'getSubSemanticData'   => array()
		) );

		$mockStore = $mockBuilder->newObject( 'Store', array(
			'getSemanticData' => $mockData,
			'changeTitle'     => array( $this, 'mockStoreOnChangeTitle' )
		) );

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $mockStore );

		return $context;
	}

	/**
	 * @since 1.9
	 */
	public function testArticlePurgeOnDatabase() {

		$context = $this->newExtensionContext();

		$this->runExtensionSetup( $context );

		$title    = Title::newFromText( __METHOD__ );
		$wikiPage = new WikiPage( $title );

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

		$this->deletePage( $title );

	}

	/**
	 * A job related test can be found MwJobWithSQLStoreIntegrationTest
	 *
	 * @since 1.9.0.1
	 */
	public function testArticleDeleteOnDatabaseAndSQLStore() {

		$store   = $this->getStore();
		$context = $this->newExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', false );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', false );

		$this->runExtensionSetup( $context );

		$semanticDataValidator = new SemanticDataValidator;

		$title    = Title::newFromText( __METHOD__ );
		$wikiPage = new WikiPage( $title );

		$dataItem = DIWikiPage::newFromTitle( $wikiPage->getTitle() );

		$this->editPageAndFetchInfo( $wikiPage, __METHOD__ );
		$semanticDataValidator->assertThatSemanticDataIsNotEmpty( $store->getSemanticData( $dataItem ) );

		$this->deletePage( $title );
		$semanticDataValidator->assertThatSemanticDataIsEmpty( $store->getSemanticData( $dataItem ) );

	}

	/**
	 * @since 1.9
	 */
	public function testOnNewRevisionFromEditCompleteOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$text = '[[Quuy::bar]]';

		$title    = Title::newFromText( __METHOD__ );
		$wikiPage = new WikiPage( $title );

		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__, $text );

		$this->assertInstanceOf( 'ParserOutput', $editInfo->output );
		$parserData = new ParserData( $wikiPage->getTitle(), $editInfo->output );

		$expected = array(
			'propertyKeys' => array( '_SKEY', '_MDAT', 'Quuy' )
		);

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $parserData->getData() );

		$this->deletePage( $title );

	}

	/**
	 * @since 1.9
	 */
	public function testOnOutputPageParserOutputeOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$title    = Title::newFromText( __METHOD__ );
		$wikiPage = new WikiPage( $title );

		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__ );
		$parserOutput = $editInfo->output;

		$this->assertInstanceOf( 'ParserOutput', $parserOutput );

		$context = new RequestContext();
		$context->setTitle( $wikiPage->getTitle() );
		$context->getOutput()->addParserOutputNoText( $parserOutput );

		$this->deletePage( $title );

	}

	/**
	 * @since 1.9
	 */
	public function testTitleMoveCompleteOnDatabase() {

		$this->runExtensionSetup( $this->newExtensionContext() );

		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$wikiPage = new WikiPage( $oldTitle );
		$editInfo = $this->editPageAndFetchInfo( $wikiPage, __METHOD__ );

		$result = $wikiPage->getTitle()->moveTo( $newTitle, false, 'test', true );

		$this->assertTrue( $result );
		$this->assertTrue( $newTitle->runOnMockStoreChangeTitleMethod );

		$this->deletePage( $oldTitle );

	}

	/**
	 * @since 1.9
	 */
	public function mockStoreOnChangeTitle( $oldTitle, $newTitle, $oldId, $newId ) {
		$newTitle->runOnMockStoreChangeTitleMethod = true ;
	}

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

}