<?php

namespace SMW\MediaWiki;

use ContentHandler;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectTargetFinder {

	/**
	 * @var Title|null
	 */
	protected $target = null;

	/**
	 * @since 2.0
	 *
	 * @param string $text
	 *
	 * @return Title|null
	 */
	public function findTarget( $text ) {
		$this->target = $this->findFromText( $text );
		return $this;
	}

	/**
	 * @since 2.0
	 *
	 * @return Title|null
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * @since 2.0
	 *
	 * @return boolean
	 */
	public function hasTarget() {
		return $this->target instanceOf Title;
	}

	private function findFromText( $text ) {

		if ( $this->hasContentHandler() ) {
			return ContentHandler::makeContent( $text, null, CONTENT_MODEL_WIKITEXT )->getRedirectTarget();
		}

		return Title::newFromRedirect( $text );
	}

	protected function hasContentHandler() {
		return defined( 'CONTENT_MODEL_WIKITEXT' );
	}

}
