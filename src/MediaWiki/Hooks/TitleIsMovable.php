<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIProperty;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class TitleIsMovable {

	/**
	 * @var Title
	 */
	private $title = null;

	/**
	 * @var boolean
	 */
	private $isMovable = true;

	/**
	 * @since  2.1
	 *
	 * @param Title &$title
	 * @param boolean &$isMovable
	 */
	public function __construct( Title $title, &$isMovable ) {
		$this->title = $title;
		$this->isMovable = &$isMovable;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function process() {
		return $this->isPropertyNamespace() ? $this->detectMovabilityForProperty() : true;
	}

	private function isPropertyNamespace() {
		return $this->title->getNamespace() === SMW_NS_PROPERTY;
	}

	private function detectMovabilityForProperty() {
		$this->isMovable = DIProperty::newFromUserLabel( $this->title->getText() )->isUserDefined();
		return true;
	}

}
