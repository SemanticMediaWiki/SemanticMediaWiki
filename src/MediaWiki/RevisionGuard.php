<?php

namespace SMW\MediaWiki;

use File;
use IDBAccessObject;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use Title;
use WikiPage;

/**
 * @private
 *
 * This class provides a single point of entry for changes that relates to the
 * MediaWiki concept of `RevisionRecord` hereby allowing an external extension to modify
 * data related to a revision in a consistent manner and lessen the potential
 * breakage during an update.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class RevisionGuard {

	use HookDispatcherAwareTrait;

	/**
	 * @var RevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @since 3.2
	 *
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct( RevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param int|null &$latestRevID
	 *
	 * @return bool
	 */
	public function isSkippableUpdate( Title $title, &$latestRevID = null ) {
		$flag = IDBAccessObject::READ_LATEST;

		if ( $latestRevID === null ) {
			$latestRevID = $title->getLatestRevID( $flag );
		}

		// If for some reason an extension decides that the current used revision
		// isn't approved then the hook should return `false`
		if ( $this->hookDispatcher->onIsApprovedRevision( $title, $latestRevID ) === false ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return int
	 */
	public function getLatestRevID( Title $title ) {
		$flag = IDBAccessObject::READ_LATEST;

		$latestRevID = $title->getLatestRevID( $flag );
		$origLatestRevID = $latestRevID;

		$this->hookDispatcher->onChangeRevisionID( $title, $latestRevID );

		if ( is_int( $latestRevID ) ) {
			return $latestRevID;
		}

		return $origLatestRevID;
	}

	/**
	 * @since 3.2
	 *
	 * @param WikiPage $page
	 *
	 * @return ?RevisionRecord
	 */
	public function newRevisionFromPage( WikiPage $page ): ?RevisionRecord {
		return $page->getRevisionRecord();
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 * @param $revId
	 * @param $flags
	 *
	 * @return ?RevisionRecord
	 */
	public function newRevisionFromTitle( Title $title, $revId = 0, $flags = 0 ): ?RevisionRecord {
		return $this->revisionLookup->getRevisionByTitle(
			$title,
			$revId,
			$flags
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param ?RevisionRecord $revision
	 *
	 * @return ?RevisionRecord
	 */
	public function getRevision( Title $title, ?RevisionRecord $revision ): ?RevisionRecord {
		if ( $revision === null ) {
			$revision = $this->newRevisionFromTitle( $title, false, IDBAccessObject::READ_NORMAL );
		}

		$origRevision = $revision;

		$this->hookDispatcher->onChangeRevision( $title, $revision );

		if ( $revision instanceof RevisionRecord ) {
			return $revision;
		}

		return $origRevision;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param File|null $file
	 *
	 * @return File|null
	 */
	public function getFile( Title $title, ?File $file = null ) {
		$origFile = $file;

		$this->hookDispatcher->onChangeFile( $title, $file );

		if ( $file instanceof File ) {
			return $file;
		}

		return $origFile;
	}

}
