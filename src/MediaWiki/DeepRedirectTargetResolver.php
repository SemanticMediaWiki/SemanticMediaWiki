<?php

namespace SMW\MediaWiki;

use RuntimeException;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DeepRedirectTargetResolver {

	/**
	 * @var PageCreator
	 */
	private $pageCreator = null;

	/**
	 * Track titles to prevent circular references caused by double redirects
	 * on the same title
	 *
	 * @var array
	 */
	private $recursiveResolverTracker = [];

	/**
	 * @since 2.1
	 *
	 * @param PageCreator $pageCreator
	 */
	public function __construct( PageCreator $pageCreator ) {
		$this->pageCreator = $pageCreator;
	}

	/**
	 * @since  2.1
	 *
	 * @param Title $title
	 *
	 * @return Title|null
	 * @throws RuntimeException
	 */
	public function findRedirectTargetFor( Title $title ) {
		return $this->doResolveRedirectTarget( $title );
	}

	protected function isValidRedirectTarget( $title ) {
		return $title instanceof Title && $title->isValidRedirectTarget();
	}

	protected function isRedirect( $title ) {
		return $title instanceof Title && $title->isRedirect();
	}

	private function doResolveRedirectTarget( Title $title ) {

		$this->addToResolverTracker( $title );

		if ( $this->isCircularByKnownRedirectTarget( $title ) ) {
			throw new RuntimeException( "Circular redirect for {$title->getPrefixedDBkey()} detected." );
		}

		if ( $this->isRedirect( $title ) ) {
			$title = $this->pageCreator->createPage( $title )->getRedirectTarget();

			if ( $title instanceof Title ) {
				$title = $this->doResolveRedirectTarget( $title );
			}
		}

		if ( $this->isValidRedirectTarget( $title ) ) {
			return $title;
		}

		throw new RuntimeException( "Redirect target is unresolvable" );
	}

	private function addToResolverTracker( $title ) {

		if ( !isset( $this->recursiveResolverTracker[$title->getPrefixedDBkey()] ) ) {
			$this->recursiveResolverTracker[$title->getPrefixedDBkey()] = 0;
		}

		return $this->recursiveResolverTracker[$title->getPrefixedDBkey()]++;
	}

	private function isCircularByKnownRedirectTarget( $title ) {
		return isset( $this->recursiveResolverTracker[$title->getPrefixedDBkey()] ) && $this->recursiveResolverTracker[$title->getPrefixedDBkey()] > 1;
	}

}
