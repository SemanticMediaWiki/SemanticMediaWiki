<?php

namespace SMW\MediaWiki\Search;

use File;
use MediaWiki\Title\Title;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResult extends \SearchResult {

	/**
	 * @var string|null
	 */
	protected $mText;

	/**
	 * @var bool
	 */
	private $hasHighlight = false;

	/**
	 * @since 3.1
	 */
	public function __construct( protected $mTitle ) {
	}

	/**
	 * @return Title|null
	 */
	public function getTitle() {
		return $this->mTitle;
	}

	/**
	 * @return File|null
	 */
	public function getFile() {
		return null;
	}

	/**
	 * @see SearchResult::getTextSnippet
	 */
	public function getTextSnippet( $terms = [] ) {
		if ( $this->hasHighlight ) {
			return str_replace( [ '<em>', '</em>' ], [ "<span class='searchmatch'>", '</span>' ], $this->mText );
		}

		return $this->mText ?? '';
	}

	/**
	 * @see SearchResult::getSectionTitle
	 */
	public function getSectionTitle() {
		if ( !isset( $this->mTitle ) || $this->mTitle->getFragment() === '' ) {
			return null;
		}

		return $this->mTitle;
	}

	/**
	 * @see SearchResult::isBrokenTitle
	 */
	public function isBrokenTitle() {
		return $this->mTitle === null;
	}

	/**
	 * @see SearchResult::isMissingRevision
	 */
	public function isMissingRevision() {
		if ( $this->mTitle == null ) {
			return true;
		}

		if ( $this->mTitle->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $this->mTitle->getDBKey() );

			// Predefined properties do not necessarily have a page and hereby a
			// a revision in MediaWiki, anyway the page exists so allow it
			// to be displayed
			if ( !$property->isUserDefined() ) {
				return false;
			}
		}

		return !$this->mTitle->exists();
	}

	/**
	 * Set a text excerpt retrieved from a different back-end.
	 *
	 * @param string|null $text
	 * @param bool $hasHighlight
	 */
	public function setExcerpt( $text = null, $hasHighlight = false ) {
		$this->mText = $text;
		$this->hasHighlight = $hasHighlight;
	}

	/**
	 * @return string|null
	 */
	public function getExcerpt() {
		return $this->mText;
	}

	/**
	 * @see SearchResult::getTitleSnippet
	 */
	public function getTitleSnippet() {
		if ( !isset( $this->mTitle ) ) {
			return '';
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			DIWikiPage::newFromTitle( $this->mTitle )
		);

		// Will return the DISPLAYTITLE, if available
		return $dataValue->getPreferredCaption();
	}

	/**
	 * @return string
	 */
	public function getRedirectSnippet() {
		return '';
	}

	/**
	 * @return Title|null
	 */
	public function getRedirectTitle() {
		return null;
	}

	/**
	 * @return string
	 */
	public function getSectionSnippet() {
		return '';
	}

	/**
	 * @return string
	 */
	public function getCategorySnippet() {
		return '';
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return '';
	}

	/**
	 * @return int
	 */
	public function getWordCount() {
		return 0;
	}

	/**
	 * @return int
	 */
	public function getByteSize() {
		return 0;
	}

	/**
	 * @return string
	 */
	public function getInterwikiPrefix() {
		return '';
	}

	/**
	 * @return string
	 */
	public function getInterwikiNamespaceText() {
		return '';
	}

	/**
	 * @return bool
	 */
	public function isFileMatch(): bool {
		return false;
	}

}
