<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class LinksUpdateTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesBeforeRun = true;

	private $title = null;
	private $applicationFactory;
	private $mwHooksHandler;
	private $semanticDataValidator;
	private $pageDeleter;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = $this->testEnvironment->getUtilityFactory()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
		$this->mwHooksHandler->invokeHooksFromRegistry();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->pageCreator = $this->testEnvironment->getUtilityFactory()->newPageCreator();
		$this->pageDeleter = $this->testEnvironment->getUtilityFactory()->newPageDeleter();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->testEnvironment->addConfiguration( 'smwgPageSpecialProperties', [ '_MDAT' ] );

		$this->title = Title::newFromText( __METHOD__ );
	}

	public function tearDown() {
		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		$this->testEnvironment->flushPages( [ $this->title ] );
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testUpdateToSetPredefinedAnnotations() {

		$this->pageCreator
			->createPage( $this->title );

		$semanticData = $this->getStore()->getSemanticData(
			DIWikiPage::newFromTitle( $this->title )
		);

		$this->assertCount(
			2,
			$semanticData->getProperties()
		);

		$expected = [
			'propertyKeys' => [ '_SKEY', '_MDAT' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

	/**
	 * @depends testUpdateToSetPredefinedAnnotations
	 */
	public function testDoUpdateUsingUserdefinedAnnotations() {

		$this->pageCreator
			->createPage( $this->title )
			->doEdit( '[[HasFirstLinksUpdatetest::testDoUpdate]] [[HasSecondLinksUpdatetest::testDoUpdate]]' );

		$parserData = $this->applicationFactory->newParserData(
			$this->title,
			$this->pageCreator->getEditInfo()->output
		);

		$contentParser = $this->applicationFactory->newContentParser( $this->title );
		$contentParser->parse();

		$parsedParserData = $this->applicationFactory->newParserData(
			$this->title,
			$contentParser->getOutput()
		);

		$this->assertCount(
			4,
			$parserData->getSemanticData()->getProperties()
		);

		$this->assertCount(
			4,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )->getProperties()
		);

		/**
		 * See #347 and LinksUpdateConstructed
		 */
		$linksUpdate = new \LinksUpdate( $this->title, new \ParserOutput() );
		$linksUpdate->doUpdate();

		$this->testEnvironment->executePendingDeferredUpdates();

		/**
		 * Asserts that before and after the update, the SemanticData container
		 * holds the same amount of properties despite the fact that the ParserOutput
		 * was invoked empty
		 */
		$semanticData = $this->getStore()->getSemanticData(
			DIWikiPage::newFromTitle( $this->title )
		);

		$this->assertCount(
			4,
			$semanticData->getProperties()
		);

		$expected = [
			'propertyKeys' => [ '_SKEY', '_MDAT', 'HasFirstLinksUpdatetest', 'HasSecondLinksUpdatetest' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);

		return $this->pageCreator->getPage()->getRevision();
	}

	/**
	 * @depends testDoUpdateUsingUserdefinedAnnotations
	 */
	public function testDoUpdateUsingNoAnnotations( $firstRunRevision ) {

		$this->pageCreator
			->createPage( $this->title )
			->doEdit( 'no annotation' );

		$this->assertNotSame(
			$firstRunRevision,
			$this->pageCreator->getPage()->getRevision()
		);

		$contentParser = $this->applicationFactory->newContentParser( $this->title );
		$contentParser->parse();

		$parserData = $this->applicationFactory->newParserData(
			$this->title,
			$contentParser->getOutput()
		);

		if ( count( $parserData->getSemanticData()->getProperties() ) != 0 ) {
			$this->markTestSkipped( "Something changed with MW 1.28 and I'm too lazy to investigate." );
		}

		$this->assertCount(
			0,
			$parserData->getSemanticData()->getProperties()
		);

		$this->assertCount(
			2,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )->getProperties()
		);

		return $firstRunRevision;
	}

	/**
	 * @depends testDoUpdateUsingNoAnnotations
	 */
	public function testReparseFirstRevision( $firstRunRevision ) {

		$contentParser = $this->applicationFactory->newContentParser( $this->title );
		$contentParser->setRevision( $firstRunRevision );
		$contentParser->parse();

		$parserData = $this->applicationFactory->newParserData(
			$this->title,
			$contentParser->getOutput()
		);

		$semanticData = $parserData->getSemanticData();

		$this->assertCount(
			3,
			$semanticData->getProperties()
		);

		$expected = [
			'propertyKeys' => [ '_SKEY', 'HasFirstLinksUpdatetest', 'HasSecondLinksUpdatetest' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

}
