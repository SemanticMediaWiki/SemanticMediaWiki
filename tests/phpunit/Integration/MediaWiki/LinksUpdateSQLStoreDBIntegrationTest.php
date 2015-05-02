<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\ApplicationFactory;
use SMW\ParserData;
use SMW\DIWikiPage;
use SMW\ContentParser;

use ParserOutput;
use LinksUpdate;
use Revision;
use WikiPage;
use Title;
use User;

use UnexpectedValueException;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class LinksUpdateSQLStoreDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesBeforeRun = true;

	private $title = null;
	private $applicationFactory;
	private $mwHooksHandler;
	private $semanticDataValidator;
	private $pageDeleter;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->pageDeleter = new PageDeleter();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', array( '_MDAT' ) );
	}

	public function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		if ( $this->title !== null ) {
			$this->pageDeleter->deletePage( $this->title );
		}

		parent::tearDown();
	}

	public function testPageCreationAndRevisionHandlingBeforeLinksUpdate() {

		$this->title = Title::newFromText( __METHOD__ );

		$beforeAlterationRevId = $this->createSinglePageWithAnnotations();
		$this->assertSemanticDataBeforeContentAlteration();

		$afterAlterationRevId = $this->alterPageContentToCreateNewRevisionWithoutAnnotations();
		$this->assertSemanticDataAfterContentAlteration();

		$this->assertNotSame(
			$beforeAlterationRevId,
			$afterAlterationRevId
		);
	}

	/**
	 * @depends testPageCreationAndRevisionHandlingBeforeLinksUpdate
	 * @dataProvider propertyCountProvider
	 */
	public function testLinksUpdateAndVerifyStoreUpdate( $expected ) {

		$this->title = Title::newFromText( __METHOD__ );

		$beforeAlterationRevId = $this->createSinglePageWithAnnotations();
		$afterAlterationRevId  = $this->alterPageContentToCreateNewRevisionWithoutAnnotations();

		$this->fetchRevisionAndRunLinksUpdater(
			$expected['beforeAlterationRevId'],
			$beforeAlterationRevId
		);

		$this->fetchRevisionAndRunLinksUpdater(
			$expected['afterAlterationRevId'],
			$afterAlterationRevId
		);
	}

	protected function createSinglePageWithAnnotations() {
		$pageCreator = new PageCreator();
		$pageCreator->createPage( $this->title )->doEdit( 'Add user property Aa and Fuyu {{#set:|Aa=Bb|Fuyu=Natsu}}' );
		return $pageCreator->getPage()->getRevision()->getId();
	}

	protected function alterPageContentToCreateNewRevisionWithoutAnnotations() {
		$pageCreator = new PageCreator();
		$pageCreator->createPage( $this->title )->doEdit( 'No annotations' );
		return $pageCreator->getPage()->getRevision()->getId();
	}

	protected function assertSemanticDataBeforeContentAlteration() {

		$wikiPage = WikiPage::factory( $this->title );
		$revision = $wikiPage->getRevision();

		$parserData = $this->retrieveAndLoadData();
		$this->assertCount( 3, $parserData->getData()->getProperties() );

		$this->assertEquals(
			$parserData->getData(),
			$this->retrieveAndLoadData( $revision->getId() )->getData(),
			'Asserts that data are equals with or without a revision'
		);

		$this->semanticDataValidator->assertThatSemanticDataHasPropertyCountOf(
			4,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) ),
			'Asserts property Aa, Fuyu, _SKEY, and _MDAT exists'
		);
	}

	protected function assertSemanticDataAfterContentAlteration() {
		$this->semanticDataValidator->assertThatSemanticDataHasPropertyCountOf(
			2,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) ),
			'Asserts property _SKEY and _MDAT exists'
		);
	}

	protected function fetchRevisionAndRunLinksUpdater( array $expected, $revId ) {

		$parserData = $this->retrieveAndLoadData( $revId );

		// Status before the update
		$this->assertPropertyCount( $expected['poBefore'], $expected['storeBefore'], $parserData );

		$this->runLinksUpdater( $this->title, $parserData->getOutput() );

		// Status after the update
		$this->assertPropertyCount( $expected['poAfter'], $expected['storeAfter'], $parserData );
	}

	protected function assertPropertyCount( $poExpected, $storeExpected, $parserData ) {
		$this->semanticDataValidator->assertThatSemanticDataHasPropertyCountOf(
			$poExpected['count'],
			$parserData->getData(),
			$poExpected['msg']
		);

		$this->semanticDataValidator->assertThatSemanticDataHasPropertyCountOf(
			$storeExpected['count'],
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) ),
			$storeExpected['msg']
		);
	}

	protected function runLinksUpdater( Title $title, $parserOutput ) {
		$linksUpdate = new LinksUpdate( $title, $parserOutput );
		$linksUpdate->doUpdate();
	}

	protected function retrieveAndLoadData( $revId = null ) {

		$revision = $revId ? Revision::newFromId( $revId ) : null;

		$contentParser = new ContentParser( $this->title );
		$parserOutput =	$contentParser->setRevision( $revision )->parse()->getOutput();

		if ( $parserOutput instanceof ParserOutput ) {
			return new ParserData( $this->title, $parserOutput );
		}

		throw new UnexpectedValueException( 'ParserOutput is missing' );
	}

	public function propertyCountProvider() {

		// Property _SKEY is always present even within an empty container
		// po = ParserOutput, before means prior LinksUpdate

		$provider = array();

		$provider[] = array( array(
			'beforeAlterationRevId' => array(
				'poBefore'  => array(
					'count' => 3,
					'msg'   => 'Asserts property Aa, Fuyu, and _SKEY exists before the update'
				),
				'storeBefore' => array(
					'count'   => 2,
					'msg'     => 'Asserts property _SKEY and _MDAT exists in Store before the update'
				),
				'poAfter'    => array(
					'count'  => 4,
					'msg'    => 'Asserts property Aa, Fuyu, _SKEY, and _MDAT exists after the update'
				),
				'storeAfter' => array(
					'count'  => 4,
					'msg'    => 'Asserts property Aa, Fuyu, _SKEY, and _MDAT exists after the update'
				)
			),
			'afterAlterationRevId' => array(
				'poBefore'  => array(
					'count' => 0,
					'msg'   => 'Asserts no property exists before the update'
				),
				'storeBefore' => array(
					'count'   => 4,
					'msg'     => 'Asserts property Aa, Fuyu, _SKEY, and _MDAT from the previous state as no update has been made yet'
				),
				'poAfter'    => array(
					'count'  => 0,
					'msg'    => 'Asserts property _MDAT exists after the update'
				),
				'storeAfter' => array(
					'count'  => 2,
					'msg'    => 'Asserts property _SKEY, _MDAT exists after the update'
				)
			)
		) );

		return $provider;
	}

}
