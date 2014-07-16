<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;
use SMW\Tests\MwDBSQLStoreIntegrationTestCase;

use SMW\ExtensionContext;
use SMW\SemanticData;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\ParserData;
use SMW\DIProperty;
use SMW\DIWikiPage;

use RequestContext;
use WikiPage;
use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class IndirectFunctionHookValidationDBIntegrationTest extends MwDBSQLStoreIntegrationTestCase {

	protected $title;

	protected function tearDown() {
		$pageDeleter = new PageDeleter();
		$pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	protected function getContext() {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgPageSpecialProperties', array( DIProperty::TYPE_MODIFICATION_DATE ) );
		$context->getSettings()->set( 'smwgNamespacesWithSemanticLinks', array( NS_MAIN => true ) );
		$context->getSettings()->set( 'smwgCacheType', 'hash' );
		$context->getSettings()->set( 'smwgAutoRefreshOnPurge', true );
		$context->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', false );
		$context->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', false );

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $this->getStore() );

		return $context;
	}

	public function testPagePurge() {

		$context = $this->getContext();

		$this->runExtensionSetup( $context );

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page purge]]' );

		$pageCreator
			->getPage()
			->doPurge();

		$result = $context->getDependencyBuilder()
			->newObject( 'CacheHandler' )
			->setKey( ArticlePurge::newCacheId( $this->title->getArticleID() ) )
			->get();

		$this->assertTrue( $result );
	}

	public function testPageDelete() {

		$this->runExtensionSetup( $this->getContext() );

		$semanticDataValidator = new SemanticDataValidator();

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page delete]]' );

		$semanticDataValidator->assertThatSemanticDataIsNotEmpty(
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )
		);

		$this->deletePage( $this->title );

		$semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )
		);
	}

	public function testEditPageToGetNewRevision() {

		$this->runExtensionSetup( $this->getContext() );

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::new revision]]' );

		$parserOutput = $pageCreator->getEditInfo()->output;

		$this->assertInstanceOf(
			'ParserOutput',
			$parserOutput
		);

		$parserData = new ParserData(
			$this->title,
			$parserOutput
		);

		$expected = array(
			'propertyKeys' => array( '_SKEY', '_MDAT', 'Has_function_hook_test' )
		);

		$semanticDataValidator = new SemanticDataValidator();

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testOnOutputPageParserOutputeOnDatabase() {

		$this->runExtensionSetup( $this->getContext() );

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::output page]]' );

		$parserOutput = $pageCreator->getEditInfo()->output;

		$this->assertInstanceOf(
			'ParserOutput',
			$parserOutput
		);

		$context = new RequestContext();
		$context->setTitle( $this->title );

		// Use of OutputPage::addParserOutputNoText was deprecated in MediaWiki 1.24
		if ( method_exists( $context->getOutput(), 'addParserOutputMetadata' ) ) {
			$context->getOutput()->addParserOutputMetadata( $parserOutput );
		} else {
			$context->getOutput()->addParserOutputNoText( $parserOutput );
		}
	}

	public function testPageMove() {

		$this->runExtensionSetup( $this->getContext() );

		$this->title = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$this->assertNull(
			WikiPage::factory( $newTitle )->getRevision()
		);

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page move]]' );

		$pageCreator
			->getPage()
			->getTitle()
			->moveTo( $newTitle, false, 'test', true );

		$this->assertNotNull(
			WikiPage::factory( $newTitle )->getRevision()
		);
	}

}
