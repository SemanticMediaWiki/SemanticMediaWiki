<?php

namespace SMW\MediaWiki;

use IDBAccessObject;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use SMW\PageInfo;
use SMW\Schema\Content\Content;
use Title;
use User;
use WikiFilePage;
use WikiPage;

/**
 * Provide access to MediaWiki objects relevant for the predefined property
 * annotation process
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PageInfoProvider implements PageInfo {

	use RevisionGuardAwareTrait;

	/**
	 * @var WikiPage
	 */
	private $wikiPage = null;

	/**
	 * @var RevisionRecord
	 */
	private $revision = null;

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @var RevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 * @param ?RevisionRecord $revision
	 * @param ?User $user
	 */
	public function __construct(
		WikiPage $wikiPage,
		?RevisionRecord $revision = null,
		?User $user = null
	) {
		$this->wikiPage = $wikiPage;
		$this->revision = $revision;
		$this->user = $user;
	}

	/**
	 * @since 1.9
	 *
	 * @return integer
	 */
	public function getModificationDate() {
		return $this->wikiPage->getTimestamp();
	}

	/**
	 * @note getFirstRevision() is expensive as it initiates a read on the
	 * revision table which is not cached
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	public function getCreationDate() {
		return $this->revisionLookup->getFirstRevision(
			$this->wikiPage->getTitle(),
			IDBAccessObject::READ_LATEST
		)->getTimestamp();
	}

	/**
	 * @note Using isNewPage() is expensive due to access to the database
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isNewPage() {

		if ( $this->isFilePage() ) {
			return isset( $this->wikiPage->smwFileReUploadStatus ) ? !$this->wikiPage->smwFileReUploadStatus : false;
		}

		if ( $this->revision ) {
			return $this->revision->getParentId() === null;
		}

		$revision = $this->revisionGuard->newRevisionFromPage(
			$this->wikiPage
		);

		return $revision->getParentId() === null;
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getLastEditor() {
		return $this->user ? $this->user->getUserPage() : null;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return boolean
	 */
	public function isFilePage() {
		return $this->wikiPage instanceof WikiFilePage;
	}

	/**
	 * @since 3.0
	 *
	 * @return text
	 */
	public function getNativeData() {

		if ( $this->wikiPage->getContent() === null ) {
			return '';
		}

		$content = $this->wikiPage->getContent();

		if ( $content instanceof Content ) {
			return $content->toJson();
		}

		return $content->getNativeData();
	}

	/**
	 * @since 1.9.1
	 *
	 * @return string|null
	 */
	public function getMediaType() {

		if ( $this->isFilePage() === false ) {
			return null;
		}

		return $this->wikiPage->getFile()->getMediaType();
	}

	/**
	 * @since 1.9.1
	 *
	 * @return string|null
	 */
	public function getMimeType() {

		if ( $this->isFilePage() === false ) {
			return null;
		}

		return $this->wikiPage->getFile()->getMimeType();
	}

	/**
	 * @since 4.0
	 */
	public function setRevisionLookup( RevisionLookup $revisionLookup ) {
		$this->revisionLookup = $revisionLookup;
	}

}
