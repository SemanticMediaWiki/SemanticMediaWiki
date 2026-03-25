<?php

namespace SMW\MediaWiki\Search;

use File;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;

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
	public function getFile(): ?File {
		return null;
	}

	/**
	 * @see SearchResult::getTextSnippet
	 */
	public function getTextSnippet( $terms = [] ): string {
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
	public function isBrokenTitle(): bool {
		return $this->mTitle === null;
	}

	/**
	 * @see SearchResult::isMissingRevision
	 */
	public function isMissingRevision(): bool {
		if ( $this->mTitle == null ) {
			return true;
		}

		if ( $this->mTitle->getNamespace() === SMW_NS_PROPERTY ) {
			$property = Property::newFromUserLabel( $this->mTitle->getDBKey() );

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
	public function setExcerpt( $text = null, $hasHighlight = false ): void {
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
			WikiPage::newFromTitle( $this->mTitle )
		);

		// Will return the DISPLAYTITLE, if available
		return $dataValue->getPreferredCaption();
	}

	/**
	 * @return string
	 */
	public function getRedirectSnippet(): string {
		return '';
	}

	/**
	 * @return Title|null
	 */
	public function getRedirectTitle(): ?Title {
		return null;
	}

	/**
	 * @return string
	 */
	public function getSectionSnippet(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function getCategorySnippet(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function getTimestamp(): string {
		return '';
	}

	/**
	 * @return int
	 */
	public function getWordCount(): int {
		return 0;
	}

	/**
	 * @return int
	 */
	public function getByteSize(): int {
		return 0;
	}

	/**
	 * @return string
	 */
	public function getInterwikiPrefix(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function getInterwikiNamespaceText(): string {
		return '';
	}

	/**
	 * @return bool
	 */
	public function isFileMatch(): bool {
		return false;
	}

}
