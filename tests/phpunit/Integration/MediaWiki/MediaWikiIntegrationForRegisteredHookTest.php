<?php

namespace SMW\Tests\Integration\MediaWiki;

use RequestContext;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\ParserData;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\UtilityFactory;
use Title;
use WikiPage;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiIntegrationForRegisteredHookTest extends MwDBaseUnitTestCase {

	private $title;
	private $semanticDataValidator;
	private $applicationFactory;
	private $mwHooksHandler;
	private $pageDeleter;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = [
			'smwgPageSpecialProperties' => [ '_MDAT' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
			'smwgMainCacheType' => 'hash',
			'smwgAutoRefreshOnPurge' => true
		];

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$this->pageDeleter = new PageDeleter();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		$this->pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	public function testPagePurge() {

		$cacheFactory = $this->applicationFactory->newCacheFactory();
		$cache = $cacheFactory->newFixedInMemoryCache();

		$this->applicationFactory->registerObject( 'Cache', $cache );

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page purge]]' );

		$key = $cacheFactory->getPurgeCacheKey( $this->title->getArticleID() );

		$pageCreator
			->getPage()
			->doPurge();

		$this->assertTrue(
			$cache->fetch( $key )
		);
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
			->doEdit( '[[EditPageToGetNewRevisionHookTest::Foo]]' );

		$parserOutput = $pageCreator->getEditInfo()->output;

		$this->assertInstanceOf(
			'ParserOutput',
			$parserOutput
		);

		$parserData = new ParserData(
			$this->title,
			$parserOutput
		);

		$expected = [
			'propertyKeys' => [ '_SKEY', '_MDAT', 'EditPageToGetNewRevisionHookTest' ]
		];

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
