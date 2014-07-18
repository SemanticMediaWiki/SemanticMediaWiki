<?php

namespace SMW\Tests\Util;

use SMW\Store;
use SMW\ContentParser;
use SMW\Application;
use SMW\DIWikiPage;

use Title;
use WikiPage;

use RuntimeException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 */
class PageRefresher {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @since 2.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.0
	 *
	 * @param mixed $title
	 *
	 * @return PageRefresher
	 */
	public function doRefresh( $title ) {

		if ( $title instanceOf WikiPage || $title instanceOf DIWikiPage ) {
			$title = $title->getTitle();
		}

		if ( !$title instanceOf Title ) {
			throw new RuntimeException( 'Title instance is missing' );
		}

		$contentParser = new ContentParser( $title );
		$contentParser->forceToUseParser();

		$parserData = Application::getInstance()->newParserData(
			$title,
			$contentParser->parse()->getOutput()
		);

		$parserData->updateStore();

		return $this;
	}

}
