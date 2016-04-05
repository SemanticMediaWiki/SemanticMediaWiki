<?php

namespace SMW\Tests\Utils;

use RuntimeException;
use SMW\DIWikiPage;
use SMW\SemanticData;
use Title;

/**
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class SemanticDataFactory {

	private $subject = null;

	/**
	 * @since 2.0
	 *
	 * @param Title|string $title
	 */
	public function setTitle( $title ) {

		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		if ( $title instanceof Title ) {
			return $this->setSubject( DIWikiPage::newFromTitle( $title ) );
		}

		throw new RuntimeException( "Something went wrong" );
	}

	/**
	 * @since 2.0
	 *
	 * @param DIWikiPage $subject
	 */
	public function setSubject( DIWikiPage $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @param string $title
	 *
	 * @return SemanticData
	 * @throws RuntimeException
	 */
	public function newEmptySemanticData( $title = null ) {

		if ( $title instanceof DIWikiPage ) {
			$this->setSubject( $title );
		} elseif ( $title !== null ) {
			$this->setTitle( $title );
		}

		if ( $this->subject !== null ) {
			return new SemanticData( $this->subject );
		}

		throw new RuntimeException( "Something went wrong" );
	}

	/**
	 * @since 2.0
	 */
	public function null() {
		$this->subject = null;
		return this;
	}

}
