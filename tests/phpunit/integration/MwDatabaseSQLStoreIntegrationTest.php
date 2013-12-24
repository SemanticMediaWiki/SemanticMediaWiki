<?php

namespace SMW\Test;

use SMW\StoreFactory;

use WikiPage;
use Title;

/**
 * @covers \SMW\Store
 *
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
class MwDatabaseSQLStoreIntegrationTest extends \MediaWikiTestCase {

	/**
	 * @var Title
	 */
	protected $title;

	public function titleProvider() {
		$provider = array();

		$provider[] = array( NS_MAIN, 'withInterWiki', 'foo' );
		$provider[] = array( NS_MAIN, 'normalTite', '' );
		$provider[] = array( NS_MAIN, 'useUpdateJobs', '' );

		return $provider;
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWithoutJobs( $ns, $name, $iw ) {
		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->createPage();

		$this->assertStoreHasDataToRefresh( false );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWitJobs( $ns, $name, $iw ) {
		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->createPage();

		$this->assertStoreHasDataToRefresh( true );
	}

	protected function createPage() {
		$pageCreator = new PageCreator();
		$pageCreator->createPage( $this->title );
	}

	public function tearDown() {
		if ( $this->title !== null ) {
			$pageDeleter = new PageDeleter();
			$pageDeleter->deletePage( $this->title );
		}

		parent::tearDown();
	}

	protected function assertStoreHasDataToRefresh( $useJobs ) {
		$refreshPosition = $this->title->getArticleID();

		$refreshProgress = $this->getStore()->refreshData(
			$refreshPosition,
			1,
			false,
			$useJobs
		);

		$this->assertGreaterThan( 0, $refreshProgress );
	}

	protected function getStore() {
		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable for SMWSQLStore3' );
		}

		return $store;
	}

}

class PageCreator {

	public function createPage( Title $title ) {
		$page = new \WikiPage( $title );

		$pageContent = 'Content of ' . $title->getFullText();
		$editMessage = 'SMW system test: create page';

		if ( class_exists( 'WikitextContent' ) ) {
			$content = new \WikitextContent( $pageContent );

			$page->doEditContent(
				$content,
				$editMessage
			);
		}
		else {
			$page->doEdit( $pageContent, $editMessage );
		}
	}

}

class PageDeleter {

	public function deletePage( Title $title ) {
		$page = new \WikiPage( $title );
		$page->doDeleteArticle( 'SMW system test: delete page' );
	}

}