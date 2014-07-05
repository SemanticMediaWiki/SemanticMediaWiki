<?php

namespace SMW\Tests\Util;

use SMW\DIWikiPage;
use SMW\SemanticData;

use Title;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since   1.9.3
 *
 * @author mwjames
 */
class SemanticDataFactory {

	private $subject = null;

	/**
	 * @since 1.9.3
	 *
	 * @param Title|string $title
	 */
	public function setTitle( $title ) {

		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		if ( $title instanceOf Title ) {
			return $this->setSubject( DIWikiPage::newFromTitle( $title ) );
		}

		throw new RuntimeException( "Something went wrong" );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param DIWikiPage $subject
	 */
	public function setSubject( DIWikiPage $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @since  1.9.3
	 *
	 * @param string $title
	 *
	 * @return SemanticData
	 * @throws RuntimeException
	 */
	public function newEmptySemanticData( $title = null ) {

		if ( $title !== null ) {
			$this->setTitle( $title );
		}

		if ( $this->subject instanceOf DIWikiPage ) {
			return new SemanticData( $this->subject );
		}

		throw new RuntimeException( "Something went wrong" );
	}

	/**
	 * @since 1.9.3
	 */
	public function null() {
		$this->subject = null;
		return this;
	}

}
