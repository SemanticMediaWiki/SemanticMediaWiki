<?php

namespace SMW\Tests\Utils;

use SMW\MediaWiki\Jobs\UpdateJob;

use SMW\Store;
use SMW\ContentParser;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;

use Title;
use WikiPage;

use RuntimeException;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 */
class PageRefresher {

	/**
	 * @since 2.0
	 *
	 * @param mixed $title
	 *
	 * @return PageRefresher
	 */
	public function doRefresh( $title ) {

		if ( $title instanceof WikiPage || $title instanceof DIWikiPage ) {
			$title = $title->getTitle();
		}

		if ( !$title instanceof Title ) {
			throw new RuntimeException( 'Expected a title instance' );
		}

		$contentParser = new ContentParser( $title );
		$contentParser->forceToUseParser();

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$title,
			$contentParser->parse()->getOutput()
		);

		$parserData->updateStore();

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $pages
	 *
	 * @return PageRefresher
	 */
	public function doRefreshPoolOfPages( array $pages ) {
		foreach ( $pages as $page ) {
			$this->doRefreshByUpdateJob( $page );
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param mixed $title
	 *
	 * @return PageRefresher
	 */
	public function doRefreshByUpdateJob( $title ) {

		if ( $title instanceof WikiPage || $title instanceof DIWikiPage ) {
			$title = $title->getTitle();
		}

		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		if ( !$title instanceof Title ) {
			throw new RuntimeException( 'Expected a title instance' );
		}

		$job = new UpdateJob( $title );
		$job->run();

		return $this;
	}

}
