<?php

namespace SMW\MediaWiki;

use MediaWiki\Revision\SlotRecord;
use IDBAccessObject;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MediaWikiNsContentReader {

	use RevisionGuardAwareTrait;

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
			$content = $this->readFromDatabase( $name );
		}

		return $content;
	}

	private function readFromDatabase( $name ) {

		$title = Title::makeTitleSafe( NS_MEDIAWIKI, ucfirst( $name ) );

		if ( $title === null ) {
			return '';
		}

		$revision = $this->revisionGuard->newRevisionFromTitle( $title, false, IDBAccessObject::READ_LATEST );

		if ( $revision === null ) {
			return '';
		}

		return $revision->getContent( SlotRecord::MAIN )->getText();
	}

}
