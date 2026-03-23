<?php

namespace SMW\Tests\Utils;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use RuntimeException;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;

/**
 * @license GPL-2.0-or-later
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
			$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $title );
		}

		if ( $title instanceof Title ) {
			return $this->setSubject( WikiPage::newFromTitle( $title ) );
		}

		throw new RuntimeException( "Something went wrong" );
	}

	/**
	 * @since 2.0
	 *
	 * @param WikiPage $subject
	 */
	public function setSubject( WikiPage $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @param string|null $title
	 *
	 * @return SemanticData
	 * @throws RuntimeException
	 */
	public function newEmptySemanticData( $title = null ) {
		if ( $title instanceof WikiPage ) {
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
