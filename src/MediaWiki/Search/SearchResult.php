<?php

namespace SMW\MediaWiki\Search;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use Title;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResult extends \SearchResult {

	/**
	 * @var boolean
	 */
	private $hasHighlight = false;

	/**
	 * @since 3.1
	 *
	 * @param Title|null $title
	 */
	public function __construct( $title ) {
		$this->initFromTitle( $title );
	}

	/**
	 * @see SearchResult::getTextSnippet
	 */
	function getTextSnippet( $terms = [] ) {

		if ( $this->hasHighlight ) {
			return str_replace( [ '<em>', '</em>' ], [ "<span class='searchmatch'>", '</span>' ], $this->mText );
		}

		return parent::getTextSnippet( $terms );
	}

	/**
	 * @see SearchResult::getSectionTitle
	 */
	function getSectionTitle() {

		if ( !isset( $this->mTitle ) || $this->mTitle->getFragment() === '' ) {
			return null;
		}

		return $this->mTitle;
	}

	/**
	 * @see SearchResult::isBrokenTitle
	 */
	function isBrokenTitle() {
		return $this->mTitle === null;
	}

	/**
	 * @see SearchResult::isMissingRevision
	 */
	function isMissingRevision() {

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
	 * @param string $text|null
	 * @param boolean $hasHighlight
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

}
