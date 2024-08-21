<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DataValueFactory;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\SMWIntegrationTestCase;
use UnexpectedValueException;
use Title;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class LinksUpdateTest extends SMWIntegrationTestCase {

	private $title = null;
	private $subject;
	private $applicationFactory;
	private $mwHooksHandler;
	private $semanticDataValidator;
	private $pageDeleter;
	private $pageCreator;
	private $revisionGuard;

	protected function setUp(): void {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$settings = [
			'smwgPageSpecialProperties' => [ '_MDAT' ]
		];

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$this->title = Title::newFromText( __METHOD__ );
		$this->revisionGuard = ApplicationFactory::getInstance()->singleton( 'RevisionGuard' );
	}

	public function tearDown(): void {
		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testUpdateToSetPredefinedAnnotations() {
		// $this->title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $this->title );

		$this->page = parent::getNonexistingTestPage( $this->title );
		parent::editPage( $this->page, '' );

		$semanticData = $this->getStore()->getSemanticData(
			$subject
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
		$subject = DIWikiPage::newFromTitle( $this->title );

		$this->page = parent::getExistingTestPage( $this->title );
		parent::editPage( $this->page, '' );
		$semanticData = $this->getStore()->getSemanticData( $subject );

		parent::editPage( $this->page, '[[HasFirstLinksUpdatetest::testDoUpdate]] [[HasSecondLinksUpdatetest::testDoUpdate]]' );
		$semanticData = $this->getStore()->getSemanticData( $subject );

		$this->assertCount(
			4,
			$semanticData->getProperties()
		);

		/**
		 * See #347 and LinksUpdateComplete
		 */
		$linksUpdate = new \LinksUpdate( $this->title, new \ParserOutput() );
		$linksUpdate->doUpdate();

		$expected = [
			'propertyKeys' => [ '_SKEY', '_MDAT', 'HasFirstLinksUpdatetest', 'HasSecondLinksUpdatetest' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);

		return $this->revisionGuard->newRevisionFromPage( $this->getPage() );
	}

	/**
	 * @depends testDoUpdateUsingUserdefinedAnnotations
	 */
	public function testDoUpdateUsingNoAnnotations( $firstRunRevision ) {
		$this->title = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $this->title );

		$this->page = parent::getNonexistingTestPage( $this->title );

		$this->assertNotSame(
			$firstRunRevision,
			$this->revisionGuard->newRevisionFromPage( $this->getPage() )
		);

		$semanticData = $this->getStore()->getSemanticData( $subject );

		$this->assertCount(
			0,
			$semanticData->getProperties()
		);

		parent::editPage( $this->page, '' );

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

	/**
	 * @since 2.0
	 *
	 * @return EditInfo
	 */
	public function getEditInfo( $page ) {
		$editInfo = $this->applicationFactory::getInstance()->newMwCollaboratorFactory()->newEditInfo(
			$this->page
		);

		return $editInfo->fetchEditInfo();
	}

	/**
	 * @since 1.9.1
	 *
	 * @return \WikiPage
	 * @throws UnexpectedValueException
	 */
	public function getPage() {
		if ( $this->page instanceof \WikiPage ) {
			return $this->page;
		}

		throw new UnexpectedValueException( 'Expected a WikiPage instance, use createPage first' );
	}

}
