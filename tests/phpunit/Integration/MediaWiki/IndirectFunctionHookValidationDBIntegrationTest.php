<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;
use SMW\Tests\Util\MwHooksHandler;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Application;
use SMW\Settings;

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
class IndirectFunctionHookValidationDBIntegrationTest extends MwDBaseUnitTestCase {

	private $title;
	private $semanticDataValidator;
	private $application;
	private $mwHooksHandler;
	private $pageDeleter;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();
		$this->mwHooksHandler = new MwHooksHandler();
		$this->semanticDataValidator = new SemanticDataValidator();
		$this->pageDeleter = new PageDeleter();

		$this->application->getSettings()->set( 'smwgPageSpecialProperties', array( DIProperty::TYPE_MODIFICATION_DATE ) );
		$this->application->getSettings()->set( 'smwgNamespacesWithSemanticLinks', array( NS_MAIN => true ) );
		$this->application->getSettings()->set( 'smwgCacheType', 'hash' );
		$this->application->getSettings()->set( 'smwgAutoRefreshOnPurge', true );
		$this->application->getSettings()->set( 'smwgDeleteSubjectAsDeferredJob', false );
		$this->application->getSettings()->set( 'smwgDeleteSubjectWithAssociatesRefresh', false );
	}

	protected function tearDown() {
		$this->application->clear();
		$this->mwHooksHandler->restoreListedHooks();

		$this->pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	public function testPagePurge() {

		$this->mwHooksHandler->deregisterListedHooks();
		$this->application->registerObject( 'CacheHandler', new \SMW\CacheHandler( new \HashBagOStuff() ) );

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page purge]]' );

		$id = ArticlePurge::newCacheId( $this->title->getArticleID() );

		$pageCreator
			->getPage()
			->doPurge();

		$result = Application::getInstance()
			->getcache()
			->setKey( $id )
			->get();

		$this->assertTrue( $result );
	}

	public function testPageDelete() {

		$this->mwHooksHandler->deregisterListedHooks();

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page delete]]' );

		$this->semanticDataValidator->assertThatSemanticDataIsNotEmpty(
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )
		);

		$this->pageDeleter->deletePage( $this->title );

		$this->semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )
		);
	}

	public function testEditPageToGetNewRevision() {

		$this->mwHooksHandler->deregisterListedHooks();

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

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testOnOutputPageParserOutputeOnDatabase() {

		$this->mwHooksHandler->deregisterListedHooks();

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

		$this->mwHooksHandler->deregisterListedHooks();

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
