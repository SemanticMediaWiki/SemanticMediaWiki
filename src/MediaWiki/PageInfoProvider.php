<?php

namespace SMW\MediaWiki;

use Revision;
use SMW\PageInfo;
use User;
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

	/**
	 * @var WikiPage
	 */
	private $wikiPage = null;

	/**
	 * @var Revision
	 */
	private $revision = null;

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @since 1.9
	 *
	 * @param WikiPage $wikiPage
	 * @param Revision|null $revision
	 * @param User|null $user
	 */
	public function __construct( WikiPage $wikiPage, Revision $revision = null, User $user = null ) {
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
		return $this->wikiPage->getTitle()->getFirstRevision()->getTimestamp();
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

		return $this->wikiPage->getRevision()->getParentId() === null;
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
		return $this->wikiPage instanceof \WikiFilePage;
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

		if ( $content instanceof \SMW\Schema\Content\Content ) {
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

}
