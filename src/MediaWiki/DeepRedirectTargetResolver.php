<?php

namespace SMW\MediaWiki;

use Title;
use RuntimeException;

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
	private $knownRedirects = array();

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
		return $title instanceOf Title && $title->isRedirect();
	}

	private function doResolveRedirectTarget( Title $title ) {

		if ( $this->isCircularByKnownRedirects( $title ) ) {
			throw new RuntimeException( "Circular redirect for {$title->getPrefixedDBkey()} detected." );
		}

		if ( $this->isRedirect( $title ) ) {
			$title = $this->pageCreator->createPage( $title )->getRedirectTarget();

			if ( $title instanceOf Title ) {
				$this->addToKnownRedirects( $title );
				$title = $this->doResolveRedirectTarget( $title );
			}
		}

		if ( $this->isValidRedirectTarget( $title ) ) {
			return $title;
		}

		throw new RuntimeException( "Redirect target is unresolvable" );
	}

	private function addToKnownRedirects( $title ) {

		if ( !isset( $this->knownRedirects[ $title->getPrefixedDBkey() ] ) ) {
			return $this->knownRedirects[ $title->getPrefixedDBkey() ] = false;
		}

		return $this->knownRedirects[ $title->getPrefixedDBkey() ] = true;
	}

	/**
	 * If a title is already tracked then it was inserted during a previous recurive
	 * run which means that it reached the starting point of a circular reference
	 */
	private function isCircularByKnownRedirects( $title ) {
		return isset( $this->knownRedirects[ $title->getPrefixedDBkey() ] ) && $this->knownRedirects[ $title->getPrefixedDBkey() ];
	}

}
