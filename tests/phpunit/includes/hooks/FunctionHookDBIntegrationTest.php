<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\OutputPageParserOutput;
use SMW\ArticlePurge;
use SMW\ExtensionContext;

use WikiPage;
use Title;

/**
 * @covers \SMW\FunctionHook
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FunctionHookDBIntegrationTest extends \MediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * @since 1.9
	 */
	public function newExtensionContext() {

		$context = new ExtensionContext();

		$settings = $context->getSettings();
		$settings->set( 'smwgCacheType', CACHE_NONE );

		$mockBuilder = new MockObjectBuilder();

		$data = $mockBuilder->newObject( 'SemanticData', array(
			'hasVisibleProperties' => false,
		) );

		$store = $mockBuilder->newObject( 'Store', array(
			'getSemanticData' => $data,
		) );

		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );

		return $context;
	}

	/**
	 * @since 1.9
	 */
	public function newWikiPage( $text = 'Foo' ) {

		if ( !method_exists( 'WikiPage', 'doEditContent' ) ) {
			$this->markTestSkipped(
				'Skipped test due to missing method (probably MW 1.19 or lower).'
			);
		}

		$wikiPage = new WikiPage( Title::newFromText( $text ) );
		$user = new MockSuperUser();

		$content = \ContentHandler::makeContent(
			'testing',
			$wikiPage->getTitle(),
			CONTENT_MODEL_WIKITEXT
		);

		$wikiPage->doEditContent( $content, "testing " . __METHOD__, EDIT_NEW, false, $user );

		return $wikiPage;
	}

	/**
	 * @since 1.9
	 */
	public function testOnArticlePurgeOnDatabase() {

		$wikiPage = $this->newWikiPage( __METHOD__ );

		$instance = new ArticlePurge( $wikiPage );
		$instance->invokeContext( $this->newExtensionContext() );

		$this->assertTrue( $instance->process() );

		// Always make sure to clean-up
		if ( $wikiPage->exists() ) {
			$wikiPage->doDeleteArticle( "testing done on " . __METHOD__ );
		}

	}

	/**
	 * @since 1.9
	 */
	public function testOnNewRevisionFromEditCompleteOnDatabase() {

		$wikiPage = $this->newWikiPage( __METHOD__ );

		$this->assertTrue( $wikiPage->getId() > 0, "WikiPage should have new page id" );
		$revision = $wikiPage->getRevision();
		$user = new MockSuperUser();

		$instance = new NewRevisionFromEditComplete( $wikiPage, $revision, $wikiPage->getId(), $user );
		$instance->invokeContext( $this->newExtensionContext() );

		$this->assertTrue( $instance->process() );

		// Always make sure the clean-up
		if ( $wikiPage->exists() ) {
			$wikiPage->doDeleteArticle( "testing done on " . __METHOD__ );
		}

	}

	/**
	 * @since 1.9
	 */
	public function testOnOutputPageParserOutputeOnDatabase() {

		$text = __METHOD__;
		$wikiPage = $this->newWikiPage( __METHOD__ );

		$title = $wikiPage->getTitle();

		$parserOutput = new \ParserOutput();
		$parserOutput->setTitleText( $title->getPrefixedText() );

		$context = new \RequestContext();
		$context->setTitle( $title );
		$outputPage = new \OutputPage( $context );

		$instance = new OutputPageParserOutput( $outputPage, $parserOutput );
		$instance->invokeContext( $this->newExtensionContext() );

		$this->assertTrue( $instance->process() );

		// Always make sure the clean-up
		if ( $wikiPage->exists() ) {
			$wikiPage->doDeleteArticle( "testing done on " . __METHOD__ );
		}

	}

	/**
	 * @since 1.9
	 */
	public function testTitleMoveCompleteOnDatabase() {

		// For some mysterious reasons this test causes
		// SMW\Test\ApiAskTest::testExecute ... to fail with DBQueryError:
		// Query: SELECT  o_serialized AS v0  FROM unittest_unittest_smw_fpt_mdat
		// WHERE s_id='5'; it seems that the temp. unittest tables are
		// being deleted while this test runs
		$skip = true;

		if ( !$skip && method_exists( 'WikiPage', 'doEditContent' ) ) {
			$wikiPage = $this->newPage();
			$user = $this->getUser();

			$title = $wikiPage->getTitle();
			$newTitle = $this->getTitle();
			$pageid = $wikiPage->getId();

			$content = \ContentHandler::makeContent(
				'testing',
				$title,
				CONTENT_MODEL_WIKITEXT
			);
			$wikiPage->doEditContent( $content, "testing", EDIT_NEW, false, $user );

		//	$result = SMWHooks::onTitleMoveComplete( $title, $newTitle, $user, $pageid, $pageid );

			// Always make sure to clean-up
			if ( $wikiPage->exists() ) {
				$wikiPage->doDeleteArticle( "testing done." );
			}

			$this->assertTrue( $result );
		} else {
			$this->assertTrue( $skip );
		}
	}

}
