<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
use Title;
use TitleArray;
use WikiFilePage;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TitleFactory {

	/**
	 * @since  2.0
	 *
	 * @param string $text
	 *
	 * @return Title|null
	 */
	public function newFromText( $text, $namespace = null ) {

		if ( $namespace === null ) {
			$namespace = NS_MAIN;
		}

		return Title::newFromText( $text, $namespace );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 *
	 * @return Title|null
	 */
	public function newFromID( $id ) {
		return Title::newFromID( $id );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $ids
	 *
	 * @return Title[]|TitleArray
	 */
	public function newFromIDs( $ids ) {
		if ( version_compare( MW_VERSION, '1.38', '>=' ) ) {
			$store = MediaWikiServices::getInstance()->getPageStore();

			$query = $store->newSelectQueryBuilder()
				->fields( $store->getSelectFields() )
				->where( [ 'page_id' => $ids ] );

			return TitleArray::newFromResult( $query->fetchResultSet() );
		} else {
			return Title::newFromIDs( $ids );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param int $ns
	 * @param string $title
	 * @param string $fragment
	 * @param string $interwiki
	 *
	 * @return Title|null
	 */
	public function makeTitleSafe( $ns, $title, $fragment = '', $interwiki = '' ) {
		return Title::makeTitleSafe( $ns, $title, $fragment, $interwiki );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return WikiPage
	 */
	public function createPage( Title $title ) {
		if ( version_compare( MW_VERSION, '1.36', '>=' ) ) {
			return MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		}

		return WikiPage::factory( $title );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return WikiFilePage
	 */
	public function createFilePage( Title $title ) {
		return new WikiFilePage( $title );
	}

}
