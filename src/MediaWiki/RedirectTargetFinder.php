<?php

namespace SMW\MediaWiki;

use MediaWiki\Content\ContentHandler;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectTargetFinder {

	private ?Title $redirectTarget = null;

	/**
	 * @since 2.0
	 */
	public function findRedirectTargetFromText( string $text ): static {
		if ( $this->redirectTarget === null ) {
			$this->redirectTarget = $this->findFromText( $text );
		}

		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function setRedirectTarget( ?Title $redirectTarget = null ): void {
		$this->redirectTarget = $redirectTarget;
	}

	/**
	 * @since 2.0
	 */
	public function getRedirectTarget(): ?Title {
		return $this->redirectTarget;
	}

	/**
	 * @since 2.0
	 */
	public function hasRedirectTarget(): bool {
		return $this->redirectTarget instanceof Title;
	}

	private function findFromText( string $text ): ?Title {
		return ContentHandler::makeContent( $text, null, CONTENT_MODEL_WIKITEXT )->getRedirectTarget();
	}

	protected function hasContentHandler(): bool {
		return defined( 'CONTENT_MODEL_WIKITEXT' );
	}

}
