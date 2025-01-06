<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\MediaWiki\HookListener;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class TitleIsMovable implements HookListener {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @since  2.1
	 *
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		$this->title = $title;
	}

	/**
	 * @since 2.1
	 *
	 * @param bool &$isMovable
	 *
	 * @return bool
	 */
	public function process( &$isMovable ) {
		// We don't allow rule pages to be moved as we cannot track JSON content
		// as redirects and therefore invalidate any rule assignment without a
		// possibility to automatically reassign IDs
		if ( $this->title->getNamespace() === SMW_NS_SCHEMA ) {
			$isMovable = false;
		}

		if ( $this->title->getNamespace() !== SMW_NS_PROPERTY ) {
			return true;
		}

		// Predefined properties cannot be moved!
		if ( !DIProperty::newFromUserLabel( $this->title->getText() )->isUserDefined() ) {
			$isMovable = false;
		}

		return true;
	}

}
