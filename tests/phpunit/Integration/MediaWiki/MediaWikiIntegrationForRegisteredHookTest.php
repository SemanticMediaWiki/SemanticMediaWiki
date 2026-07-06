<?php

namespace SMW\Tests\Integration\MediaWiki;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiIntegrationForRegisteredHookTest extends SMWIntegrationTestCase {

	private $title;
	private $semanticDataValidator;
	private $applicationFactory;
	private $pageDeleter;

	protected function setUp(): void {
		parent::setUp();

		( new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		) )->reseatDeclarativeHandlers();

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

	protected function tearDown(): void {
		$this->applicationFactory->clear();

		( new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		) )->reseatDeclarativeHandlers();

		parent::tearDown();
	}

	public function testPagePurge() {
		// ArticlePurge writes the purge marker through SMW.ObjectCache, i.e.
		// ServicesFactory::getObjectCache(); read it back from that same
		// instance rather than an injected Cache override.
		$cache = $this->applicationFactory->getObjectCache();

		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page purge]]' );

		$key = smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, $this->title->getArticleID() );

		$pageCreator
			->getPage()
			->doPurge();

		$this->assertTrue(
			$cache->get( $key )
		);
	}

	public function testPageDelete() {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::page delete]]' );

		$this->semanticDataValidator->assertThatSemanticDataIsNotEmpty(
			$this->getStore()->getSemanticData( WikiPage::newFromTitle( $this->title ) )
		);

		$this->pageDeleter->deletePage( $this->title );

		$this->semanticDataValidator->assertThatSemanticDataIsEmpty(
			$this->getStore()->getSemanticData( WikiPage::newFromTitle( $this->title ) )
		);
	}

	public function testEditPageToGetNewRevision() {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[EditPageToGetNewRevisionHookTest::Foo]]' );

		$parserOutput = $pageCreator->getEditInfo()->getOutput();

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
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit( '[[Has function hook test::output page]]' );

		$parserOutput = $pageCreator->getEditInfo()->getOutput();

		$this->assertInstanceOf(
			'ParserOutput',
			$parserOutput
		);

		$context = new RequestContext();
		$context->setTitle( $this->title );
		$context->getOutput()->addParserOutputMetadata( $parserOutput );
	}

}
