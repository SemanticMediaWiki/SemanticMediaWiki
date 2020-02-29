<?php

namespace SMW\MediaWiki;

use SMW\Store;
use MediaWiki\Special\SpecialPageFactory as MediaWikiSpecialPageFactory;
use SMW\MediaWiki\Specials\SpecialPendingTaskList;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SpecialPageFactory {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MediaWikiSpecialPageFactory
	 */
	private $specialPageFactory;

	/**
	 * @since  3.2
	 *
	 * @param Store $store
	 * @param MediaWikiSpecialPageFactory|null $specialPageFactory
	 */
	public function __construct( Store $store, MediaWikiSpecialPageFactory $specialPageFactory = null ) {
		$this->store = $store;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $name
	 *
	 * @return SpecialPage
	 */
	public function getPage( string $name ) {

		if ( $this->specialPageFactory !== null ) {
			return $this->specialPageFactory->getPage( $name );
		}

		return \SpecialPageFactory::getPage( $name );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $name
	 * @param string|bool $subpage
	 *
	 * @return string
	 */
	public function getLocalNameFor( string $name, $subpage = false ) : string {

		if ( $this->specialPageFactory !== null ) {
			return $this->specialPageFactory->getLocalNameFor( $name, $subpage );
		}

		return \SpecialPageFactory::getLocalNameFor( $name, $subpage );
	}

	/**
	 * @since 3.2
	 *
	 * @return SpecialPendingTaskList
	 */
	public function newSpecialPendingTaskList() {
		return new SpecialPendingTaskList();
	}

}
