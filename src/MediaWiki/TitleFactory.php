<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
use SMW\Services\ServicesFactory;
use Title;
use WikiFilePage;
use WikiPage;

/**
 * @license GPL-2.0-or-later
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
	 * @param int $id
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
	 * @return Title[]
	 */
	public function newFromIDs( $ids ) {
		if ( !count( $ids ) ) {
			return [];
		}

		$container = MediaWikiServices::getInstance();

		$dbr = $container
			->getDBLoadBalancer()
			->getMaintenanceConnectionRef( DB_REPLICA );

		$fields = $container->getPageStore()->getSelectFields();

		$res = $dbr->select(
			'page',
			$fields,
			[ 'page_id' => $ids ],
			__METHOD__
		);

		$titles = [];

		foreach ( $res as $row ) {
			$titles[] = $container->getTitleFactory()->newFromRow( $row );
		}

		return $titles;
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
		return ServicesFactory::getInstance()->newPageCreator()->createPage( $title );
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
