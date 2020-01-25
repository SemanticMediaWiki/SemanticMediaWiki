<?php

namespace SMW\MediaWiki;

use Revision;
use Title;
use File;
use User;
use WikiPage;

/**
 * @private
 *
 * This class provides a single point of entry for changes that relates to the
 * MediaWiki concept of `Revision` hereby allowing an external extension to modify
 * data related to a revision in a consistent manner and lessen the potential
 * breakage during an update.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RevisionGuard {

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param integer &$latestRevID
	 *
	 * @return boolean
	 */
	public static function isSkippableUpdate( Title $title, &$latestRevID = null ) {

		// MW 1.34+
		// https://github.com/wikimedia/mediawiki/commit/b65e77a385c7423ce03a4d21c141d96c28291a60
		if ( defined( 'Title::READ_LATEST' ) && Title::GAID_FOR_UPDATE == 512 ) {
			$flag = Title::READ_LATEST;
		} else {
			$flag = Title::GAID_FOR_UPDATE;
		}

		if ( $latestRevID === null ) {
			$latestRevID = $title->getLatestRevID( $flag );
		}

		// If for some reason an extension decides that the current used revision
		// isn't approved then the hook should return `false`
		if ( \Hooks::run( 'SMW::RevisionGuard::IsApprovedRevision', [ $title, $latestRevID ] ) === false ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return integer
	 */
	public static function getLatestRevID( Title $title ) {

		// MW 1.34+
		// https://github.com/wikimedia/mediawiki/commit/b65e77a385c7423ce03a4d21c141d96c28291a60
		if ( defined( 'Title::READ_LATEST' ) && Title::GAID_FOR_UPDATE == 512 ) {
			$flag = Title::READ_LATEST;
		} else {
			$flag = Title::GAID_FOR_UPDATE;
		}

		$latestRevID = $title->getLatestRevID( $flag );
		$origLatestRevID = $latestRevID;

		\Hooks::run( 'SMW::RevisionGuard::ChangeRevisionID', [ $title, &$latestRevID ] );

		if ( is_int( $latestRevID ) ) {
			return $latestRevID;
		}

		return $origLatestRevID;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param Revision $revision
	 *
	 * @return integer
	 */
	public static function getRevision( Title $title, $revision ) {

		$origRevision = $revision;

		\Hooks::run( 'SMW::RevisionGuard::ChangeRevision', [ $title, &$revision ] );

		if ( $revision instanceof Revision ) {
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
	public static function getFile( Title $title, File $file = null ) {

		$origFile = $file;

		\Hooks::run( 'SMW::RevisionGuard::ChangeFile', [ $title, &$file ] );

		if ( $file instanceof File ) {
			return $file;
		}

		return $origFile;
	}

}
