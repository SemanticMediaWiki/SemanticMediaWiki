<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\UtilityFactory;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\Util\PageDeleter;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\DIWikiPage;
use SMW\Application;

use RequestContext;
use WikiPage;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
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

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->application = Application::getInstance();

		$settings = array(
			'smwgPageSpecialProperties' => array( '_MDAT' ),
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
			'smwgCacheType' => 'hash',
			'smwgAutoRefreshOnPurge' => true,
			'smwgDeleteSubjectAsDeferredJob' => false,
			'smwgDeleteSubjectWithAssociatesRefresh' => false
		);

		foreach ( $settings as $key => $value ) {
			$this->application->getSettings()->set( $key, $value );
		}

		$this->pageDeleter = new PageDeleter();
	}

	protected function tearDown() {
		$this->application->clear();
		$this->mwHooksHandler->restoreListedHooks();

		$this->pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	public function testPagePurge() {

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

}
