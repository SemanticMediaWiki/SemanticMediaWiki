<?php

namespace SMW\MediaWiki;

use Title;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TitleCreator {

	/**
	 * @var PageCreator
	 */
	private $pageCreator = null;

	/**
	 * @var Title
	 */
	private $title = null;

	/**
	 * @since 2.0
	 *
	 * @param PageCreator|null $pageCreator
	 */
	public function __construct( PageCreator $pageCreator = null ) {
		$this->pageCreator = $pageCreator;
	}

	/**
	 * @since  2.0
	 *
	 * @param  string $text
	 *
	 * @return TitleCreator
	 */
	public function createFromText( $text ) {
		$this->title = Title::newFromText( $text );
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @since  2.0
	 *
	 * @return TitleCreator
	 * @throws RuntimeException
	 */
	public function findRedirect() {

		if ( $this->pageCreator === null ) {
			throw new RuntimeException( "Expected a PageCreator instance" );
		}

		$this->title = $this->resolveRedirectTargetRecursively( $this->title );

		return $this;
	}

	protected function isValidRedirectTarget( $title ) {
		return $title instanceof Title && $title->isValidRedirectTarget();
	}

	protected function isRedirect( $title ) {
		return $title instanceOf Title && $title->isRedirect();
	}

	private function resolveRedirectTargetRecursively( $title ) {

		if ( $this->isRedirect( $title ) ) {
			$title = $this->resolveRedirectTargetRecursively(
				$this->pageCreator->createPage( $title )->getRedirectTarget()
			);
		}

		if ( $this->isValidRedirectTarget( $title ) ) {
			return $title;
		}

		throw new RuntimeException( "Redirect target can not be resolved" );
	}

}
