<?php

namespace SMW\MediaWiki;

use Revision;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MediaWikiNsContentReader {

	/**
	 * @var boolean
	 */
	private $skipMessageCache = false;

	/**
	 * @since 2.3
	 */
	public function skipMessageCache() {
		$this->skipMessageCache = true;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function read( $name ) {

		$content = '';

		if ( !$this->skipMessageCache && wfMessage( $name )->exists() ) {
			$content = wfMessage( $name )->inContentLanguage()->text();
		}

		if ( $content === '' ) {
			$content = $this->tryReadFromDatabase( $name );
		}

		return $content;
	}

	private function tryReadFromDatabase( $name ) {

		$title = Title::makeTitleSafe( NS_MEDIAWIKI, ucfirst( $name ) );

		if ( $title === null ) {
			return '';
		}

		// Revision::READ_LATEST is not specified in MW 1.19
		$revisionReadFlag = defined( 'Revision::READ_LATEST' ) ? Revision::READ_LATEST : 0;

		$revision = Revision::newFromTitle( $title, false, $revisionReadFlag );

		if ( $revision === null ) {
			return '';
		}

		if ( class_exists( 'WikitextContent' ) ) {
			return $revision->getContent()->getNativeData();
		}

		if ( method_exists( $revision, 'getContent') ) {
			return $revision->getContent( Revision::RAW );
		} else {
			return $revision->getRawText();
		}
	}

}
