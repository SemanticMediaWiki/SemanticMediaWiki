<?php

namespace SMW\MediaWiki;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SMW\PageInfo;
use WikiFilePage;
use Wikimedia\Rdbms\IDBAccessObject;
use WikiPage;

/**
 * Provide access to MediaWiki objects relevant for the predefined property
 * annotation process
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PageInfoProvider implements PageInfo {

	use RevisionGuardAwareTrait;

	/**
	 * @var RevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private WikiPage $wikiPage,
		private ?RevisionRecord $revision = null,
		private ?User $user = null,
		private ?bool $isReUpload = null,
	) {
	}

	/**
	 * @since 1.9
	 *
	 * @return int
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
	 * @return int
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
	 * @return bool
	 */
	public function isNewPage() {
		if ( $this->isFilePage() ) {
			return $this->isReUpload !== null ? !$this->isReUpload : false;
		}

		$revision = $this->revision ??
			$this->revisionGuard->newRevisionFromPage( $this->wikiPage );

		return $revision->getParentId() === 0;
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
	 * @return bool
	 */
	public function isFilePage(): bool {
		return $this->wikiPage instanceof WikiFilePage;
	}

	/**
	 * @since 3.0
	 *
	 * @return text
	 */
	public function getNativeData() {
		$content = $this->wikiPage->getContent();
		if ( $content === null ) {
			return '';
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
	public function setRevisionLookup( RevisionLookup $revisionLookup ): void {
		$this->revisionLookup = $revisionLookup;
	}

	public static function isProtected( Title $title, string $action = '' ) {
		return MediaWikiServices::getInstance()->getRestrictionStore()->isProtected(
			$title, $action
		);
	}

}
