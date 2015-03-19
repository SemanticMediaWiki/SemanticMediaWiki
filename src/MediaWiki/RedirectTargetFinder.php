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
	private $redirectTarget = null;

	/**
	 * @since 2.0
	 *
	 * @param string $text
	 *
	 * @return Title|null
	 */
	public function findRedirectTargetFromText( $text ) {

		if ( $this->redirectTarget === null ) {
			$this->redirectTarget = $this->findFromText( $text );
		}

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param Title|null
	 */
	public function setRedirectTarget( Title $redirectTarget = null ) {
		$this->redirectTarget = $redirectTarget;
	}

	/**
	 * @since 2.0
	 *
	 * @return Title|null
	 */
	public function getRedirectTarget() {
		return $this->redirectTarget;
	}

	/**
	 * @since 2.0
	 *
	 * @return boolean
	 */
	public function hasRedirectTarget() {
		return $this->redirectTarget instanceof Title;
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
