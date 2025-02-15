<?php

namespace SMW\MediaWiki;

use MWNamespace;

/**
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class NamespaceInfo {

	/**
	 * @var
	 */
	private $nsInfo;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceInfo|null $nsInfo
	 */
	public function __construct( $nsInfo = null ) {
		$this->nsInfo = $nsInfo;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $index
	 *
	 * @return string
	 */
	public function getCanonicalName( $index ) {
		if ( $this->nsInfo === null ) {
			return MWNamespace::getCanonicalName( $index );
		}

		return $this->nsInfo->getCanonicalName( $index );
	}

	/**
	 * @since 3.1
	 *
	 * @return
	 */
	public function getValidNamespaces() {
		if ( $this->nsInfo === null ) {
			return MWNamespace::getValidNamespaces();
		}

		return $this->nsInfo->getValidNamespaces();
	}

	/**
	 * @since 3.1
	 *
	 * @param $index
	 *
	 * @return int
	 */
	public function getSubject( $index ) {
		if ( $this->nsInfo === null ) {
			return MWNamespace::getSubject( $index );
		}

		return $this->nsInfo->getSubject( $index );
	}

}
