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
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MwLinksUpdateWithSQLStoreDBIntegrationTest extends MwIntegrationTestCase {

	/** @var Title */
	protected $title = null;

	public function testTriggerLinksUpdateManually() {

		$context = new ExtensionContext();
		$context->getSettings()->set( 'smwgPageSpecialProperties', array( '_MDAT' ) );

		$this->runExtensionSetup( $context );

		$this->title = Title::newFromText( __METHOD__ );

		$beforeAlterationRevId = $this->createSinglePageWithAnnotations();
		$this->assertSemanticDataBeforeContentAlteration();

		$afterAlterationRevId = $this->alterPageContentToCreateNewRevisionWithoutAnnotations();
		$this->assertSemanticDataAfterContentAlteration();

		$this->assertFalse(
			$beforeAlterationRevId === $afterAlterationRevId,
			'Asserts that the revId is different before and after content alteration'
		);

		// Above verifies the environment and ensures that two revisions are
		// available one with and the other without annotations

		// beforeAlterationRevId contains a total of four properties
		$expected = array(
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
		);

		$this->fetchRevisionAndRunLinksUpdater( $expected, $beforeAlterationRevId );

		// afterAlterationRevId contains no Annotatios
		$expected = array(
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
		);

		$this->fetchRevisionAndRunLinksUpdater( $expected, $afterAlterationRevId );

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

		$parserData = $this->retrieveAndLoadData();
		$this->assertCount( 3, $parserData->getData()->getProperties() );

		$this->assertEquals(
			$parserData->getData(),
			$this->retrieveAndLoadData( $revision->getId() )->getData(),
			'Asserts that both SemanticData with/out revision are equal'
		);

		$this->assertCount(
			4,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )->getProperties()
		);

	}

	protected function assertSemanticDataAfterContentAlteration() {
		$this->assertCount(
			2,
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) )->getProperties()
		);
	}

	protected function fetchRevisionAndRunLinksUpdater( array $expected, $revId ) {

		// Property _SKEY always exsists even within an empty container

		$subject    = DIWikiPage::newFromTitle( $this->title );
		$parserData = $this->retrieveAndLoadData( $revId );

		$this->assertCount(
			$expected['poBefore']['count'],
			$parserData->getData()->getProperties(),
			$expected['poBefore']['msg']
		);

		$this->assertCount(
			$expected['storeBefore']['count'],
			$this->getStore()->getSemanticData( $subject )->getProperties(),
			$expected['storeBefore']['msg']
		);

		// Above confirms the status of the Store and ParserOutput
		// "doUpdate" will initiate an appropriate of the Store and also
		// add pre-defined properties
		$this->runLinksUpdater( $this->title, $parserData->getOutput() );

		// Output and store object have been updated
		$this->assertCount(
			$expected['poAfter']['count'],
			$parserData->getData()->getProperties(),
			$expected['poAfter']['msg']
		);

		$this->assertCount(
			$expected['storeAfter']['count'],
			$this->getStore()->getSemanticData( $subject )->getProperties(),
			$expected['storeAfter']['msg']
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

	protected function tearDown() {

		if ( $this->title !== null ) {
			$this->deletePage( $this->title );
		}

		parent::tearDown();
	}

}
