<?php

namespace SMW\Tests\Integration\SQLStore;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\MwHooksHandler;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use Title;
use WikiPage;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshSQLStoreDBIntegrationTest extends MwDBaseUnitTestCase {

	private $title;
	private $mwHooksHandler;
	private $pageDeleter;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = new MwHooksHandler();
		$this->pageDeleter = new PageDeleter();
		$this->pageCreator = new PageCreator();
	}

	public function tearDown() {

		$this->mwHooksHandler->restoreListedHooks();

		if ( $this->title !== null ) {
			$this->pageDeleter->deletePage( $this->title );
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWithoutJobs( $ns, $name, $iw ) {

		$this->mwHooksHandler->deregisterListedHooks();

		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->pageCreator->createPage( $this->title  );

		$this->assertStoreHasDataToRefresh( false );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWitJobs( $ns, $name, $iw ) {

		$this->mwHooksHandler->deregisterListedHooks();

		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->pageCreator->createPage( $this->title );

		$this->assertStoreHasDataToRefresh( true );
	}

	protected function assertStoreHasDataToRefresh( $useJobs ) {
		$refreshPosition = $this->title->getArticleID();

		$entityRebuildDispatcher = $this->getStore()->refreshData(
			$refreshPosition,
			1,
			false,
			$useJobs
		);

		$entityRebuildDispatcher->rebuild( $refreshPosition );

		$this->assertGreaterThan(
			0,
			$entityRebuildDispatcher->getEstimatedProgress()
		);
	}

	public function titleProvider() {
		$provider = [];

	//	$provider[] = array( NS_MAIN, 'withInterWiki', 'commons' );
		$provider[] = [ NS_MAIN, 'normalTite', '' ];
		$provider[] = [ NS_MAIN, 'useUpdateJobs', '' ];

		return $provider;
	}

}
