<?php

namespace SMW\Tests\Utils;

use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use Title;
use User;
use WikiPage;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 1.9.1
 */
class PageDeleter {

	/**
	 * @var TestEnvironment
	 */
	private $testEnvironment;

	/**
	 * @since 2.4
	 */
	public function __construct() {
		$this->testEnvironment = new TestEnvironment();
	}

	/**
	 * @since 1.9.1
	 */
	public function deletePage( Title $title ) {
		$page = new WikiPage( $title );

		try {
			$user = User::newSystemUser( 'Maintenance script', [ 'steal' => true ] );
			$page->doDeleteArticleReal( 'SMW system test: delete page', $user );
		} catch ( \Exception $e ) {
			//
		}

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	/**
	 * @since 2.1
	 *
	 * @param array $poolOfPages
	 */
	public function doDeletePoolOfPages( array $poolOfPages ) {
		foreach ( $poolOfPages as $page ) {

			if ( $page instanceof WikiPage || $page instanceof DIWikiPage ) {
				$page = $page->getTitle();
			}

			if ( is_string( $page ) ) {
				$page = Title::newFromText( $page );
			}

			if ( !$page instanceof Title ) {
				continue;
			}

			$this->deletePage( $page );
		}

		$this->testEnvironment->executePendingDeferredUpdates();
	}

}
