<?php

namespace SMW\Test;

use SMW\ExtensionContext;
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
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Integration
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class MwLinksUpdateWithSQLStoreDBIntegrationTest extends MwIntegrationTestCase {

	/** @var Title */
	protected $title = null;

	protected function setUp() {
		parent::setUp();

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgPageSpecialProperties', array( '_MDAT' ) );

		$this->runExtensionSetup( $context );
	}

	public function testPageCreationAndRevisionHandlingBeforeLinksUpdate() {

		$this->semanticDataValidator = new SemanticDataValidator;
		$this->title = Title::newFromText( __METHOD__ );

		$beforeAlterationRevId = $this->createSinglePageWithAnnotations();
		$this->assertSemanticDataBeforeContentAlteration();

		$afterAlterationRevId = $this->alterPageContentToCreateNewRevisionWithoutAnnotations();
		$this->assertSemanticDataAfterContentAlteration();

		$this->assertNotSame(
			$beforeAlterationRevId,
			$afterAlterationRevId,
			'Asserts that the revId is different'
		);

	}

	/**
	 * @depends testPageCreationAndRevisionHandlingBeforeLinksUpdate
	 * @dataProvider propertyCountProvider
	 */
	public function testLinksUpdateAndVerifyStoreUpdate( $expected ) {

		$this->semanticDataValidator = new SemanticDataValidator;
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
					'count' => 1,
					'msg'   => 'Asserts property _SKEY exists only before the update'
				),
				'storeBefore' => array(
					'count'   => 4,
					'msg'     => 'Asserts property Aa, Fuyu, _SKEY, and _MDAT from the previous state as no update has been made yet'
				),
				'poAfter'    => array(
					'count'  => 2,
					'msg'    => 'Asserts property _SKEY, _MDAT exists after the update'
				),
				'storeAfter' => array(
					'count'  => 2,
					'msg'    => 'Asserts property _SKEY, _MDAT exists after the update'
				)
			)
		) );

		return $provider;
	}

	protected function tearDown() {

		if ( $this->title !== null ) {
			$this->deletePage( $this->title );
		}

		parent::tearDown();
	}
}
