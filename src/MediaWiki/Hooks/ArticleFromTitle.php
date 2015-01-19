<?php

namespace SMW\MediaWiki\Hooks;

use Page;
use SMW\ConceptPage as ConceptPage;
use SMWPropertyPage as PropertyPage;
use Title;

/**
 * Register special classes for displaying semantic content on Property and
 * Concept pages.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleFromTitle
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleFromTitle {

	/**
	 * @var Title
	 */
	private $title = null;

	/**
	 * @var Page
	 */
	private $article = null;

	/**
	 * @since  2.0
	 *
	 * @param Title &$title
	 * @param Page|null &$article
	 */
	public function __construct( Title &$title, Page &$article = null ) {
		$this->title = &$title;
		$this->article = &$article;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		if ( $this->title->getNamespace() === SMW_NS_PROPERTY ) {
			$this->article = new PropertyPage( $this->title );
		} elseif ( $this->title->getNamespace() === SMW_NS_CONCEPT ) {
			$this->article = new ConceptPage( $this->title );
		}

		return true;
	}

}
