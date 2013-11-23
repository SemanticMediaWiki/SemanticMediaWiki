<?php

namespace SMW;

use Revision;
use User;
use WikiPage;

/**
 * Provide access to MediaWiki objects relevant for the predefined property
 * annotation process
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiPageInfoProvider implements PageInfoProvider {

	/** @var WikiPage */
	protected $wikiPage = null;

	/** @var Revision */
	protected $revision = null;

	/** @var User */
	protected $user = null;

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
	 * @note Using isNewPage() is expensice due to access to the database
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isNewPage() {
		return $this->revision->getParentId() !== '';
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getLastEditor() {
		return $this->user->getUserPage();
	}

}