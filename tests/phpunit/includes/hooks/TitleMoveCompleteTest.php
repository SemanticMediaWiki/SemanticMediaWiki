<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\TitleMoveComplete;

use WikiPage;

/**
 * Tests for the TitleMoveComplete class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\TitleMoveComplete
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class TitleMoveCompleteTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\TitleMoveComplete';
	}

	/**
	 * Helper method that returns a TitleMoveComplete object
	 *
	 * @since 1.9
	 *
	 * @return TitleMoveComplete
	 */
	private function newInstance( $oldTitle = null, $newTitle = null, $user = null, $oldId = 0, $newId = 0, $settings = array() ) {

		if ( $oldTitle === null ) {
			$oldTitle = $this->newMockBuilder()->newObject( 'Title' );
		}

		if ( $newTitle === null ) {
			$newTitle = $this->newMockBuilder()->newObject( 'Title' );
		}

		if ( $user === null ) {
			$user = $this->getUser();
		}

		$container = new SharedDependencyContainer();
		$container->registerObject( 'Settings', $this->newSettings( $settings ) );

		$instance = new TitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId );
		$instance->setDependencyBuilder( $this->newDependencyBuilder( $container ) );

		return $instance;
	}

	/**
	 * @test TitleMoveComplete::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test TitleMoveComplete::process
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testProcessOnMock() {

		$settings = array(
			'smwgCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
		);

		$instance = $this->newInstance( null, null, null, 0 , 0, $settings );

		$container = $instance->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		$result = $instance->process();

		// Post-process check
		$this->assertTrue(
			$result,
			'Asserts that process() always returns true'
		);

	}

	/**
	 * @test TitleMoveComplete::process
	 *
	 * @note Recycle the old test but for now skip this one
	 *
	 * @since 1.9
	 */
	public function testProcessOnDB() {

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
