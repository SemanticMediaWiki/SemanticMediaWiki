<?php

namespace SMW\MediaWiki\Search;

use Content;
use DatabaseBase;
use Title;
use SearchEngine;

/**
 * Facade to the MediaWiki `SearchEngine` which doesn't allow any factory
 * or callable to construct an instance.
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author  Stephan Gambke
 */
class ExtendedSearchEngine extends SearchEngine {

	/**
	 * @var ExtendedSearch
	 */
	private $extendedSearch;

	/**
	 * @var SearchEngine
	 */
	private $fallbackSearchEngine;

	/**
	 * @see SearchEngineFactory::create
	 *
	 * @since 3.1
	 */
	public function __construct( DatabaseBase $connection = null ) {
		// It is common practice to avoid construction work in the constructor
		// but we are unable to define a factory or callable and this is the only
		// place to create an instance.
		$searchEngineFactory = new SearchEngineFactory();

		$this->fallbackSearchEngine = $searchEngineFactory->newFallbackSearchEngine(
			$connection
		);

		$this->extendedSearch = $searchEngineFactory->newExtendedSearch(
			$this->fallbackSearchEngine
		);

		$this->extendedSearch->setPrefix( $this->prefix );
		$this->extendedSearch->setNamespaces( $this->namespaces );
	}

	/**
	 * @since 3.1
	 *
	 * @param ExtendedSearch $extendedSearch
	 */
	public function setExtendedSearch( ExtendedSearch $extendedSearch ) {
		$this->extendedSearch = $extendedSearch;
	}

	/**
	 * @since 2.1
	 *
	 * @param null|SearchEngine $fallbackSearch
	 */
	public function setFallbackSearchEngine( SearchEngine $fallbackSearchEngine = null ) {
		$this->fallbackSearchEngine = $fallbackSearchEngine;
	}

	/**
	 * @since 2.1
	 *
	 * @return SearchEngine
	 */
	public function getFallbackSearchEngine() {
		return $this->fallbackSearchEngine;
	}

	/**
	 * @see SearchEngine::getValidSorts
	 *
	 * {@inheritDoc}
	 */
	public function getValidSorts() {
		return $this->extendedSearch->getValidSorts();
	}

	/**
	 * @see SearchEngine::searchTitle
	 *
	 * {@inheritDoc}
	 */
	public function searchTitle( $term ) {

		$this->extendedSearch->setNamespaces(
			$this->namespaces
		);

		return $this->extendedSearch->searchTitle( $term );
	}

	/**
	 * @see SearchEngine::searchText
	 *
	 * {@inheritDoc}
	 */
	public function searchText( $term ) {

		$this->extendedSearch->setNamespaces(
			$this->namespaces
		);

		return $this->extendedSearch->searchText( $term );
	}

	/**
	 * @see SearchEngine::supports
	 *
	 * {@inheritDoc}
	 */
	public function supports( $feature ) {
		return $this->fallbackSearchEngine->supports( $feature );
	}

	/**
	 * @see SearchEngine::normalizeText
	 *
	 * {@inheritDoc}
	 */
	public function normalizeText( $string ) {
		return $this->fallbackSearchEngine->normalizeText( $string );
	}

	/**
	 * @see SearchEngine::getTextFromContent
	 *
	 * {@inheritDoc}
	 */
	public function getTextFromContent( Title $t, Content $c = null ) {
		return $this->fallbackSearchEngine->getTextFromContent( $t, $c );
	}

	/**
	 * @see SearchEngine::textAlreadyUpdatedForIndex
	 *
	 * {@inheritDoc}
	 */
	public function textAlreadyUpdatedForIndex() {
		return $this->fallbackSearchEngine->textAlreadyUpdatedForIndex();
	}

	/**
	 * @see SearchEngine::update
	 *
	 * {@inheritDoc}
	 */
	public function update( $id, $title, $text ) {
		$this->fallbackSearchEngine->update( $id, $title, $text );
	}

	/**
	 * @see SearchEngine::updateTitle
	 *
	 * {@inheritDoc}
	 */
	public function updateTitle( $id, $title ) {
		$this->fallbackSearchEngine->updateTitle( $id, $title );
	}

	/**
	 * @see SearchEngine::delete
	 *
	 * {@inheritDoc}
	 */
	public function delete( $id, $title ) {
		$this->fallbackSearchEngine->delete( $id, $title );
	}

	/**
	 * @see SearchEngine::setFeatureData
	 *
	 * {@inheritDoc}
	 */
	public function setFeatureData( $feature, $data ) {
		parent::setFeatureData( $feature, $data );
		$this->fallbackSearchEngine->setFeatureData( $feature, $data );
	}

	/**
	 * @see SearchEngine::getFeatureData
	 *
	 * @param String $feature
	 *
	 * @return array|null
	 */
	public function getFeatureData( $feature ) {

		if ( array_key_exists( $feature, $this->features ) ) {
			return $this->features[$feature];
		}

		return null;
	}

	/**
	 * @see SearchEngine::replacePrefixes
	 *
	 * {@inheritDoc}
	 */
	public function replacePrefixes( $query ) {
		return $query;
	}

	/**
	 * @see SearchEngine::transformSearchTerm
	 *
	 * {@inheritDoc}
	 */
	public function transformSearchTerm( $term ) {
		return $term;
	}

	/**
	 * @see SearchEngine::setLimitOffset
	 *
	 * {@inheritDoc}
	 */
	public function setLimitOffset( $limit, $offset = 0 ) {
		parent::setLimitOffset( $limit, $offset );
		$this->extendedSearch->setLimitOffset( $limit, $offset );
		$this->fallbackSearchEngine->setLimitOffset( $limit, $offset );
	}

	/**
	 * @see SearchEngine::setNamespaces
	 *
	 * {@inheritDoc}
	 */
	public function setNamespaces( $namespaces ) {
		parent::setNamespaces( $namespaces );

		$this->extendedSearch->setNamespaces(
			$this->namespaces
		);

		$this->fallbackSearchEngine->setNamespaces( $namespaces );
	}

	/**
	 * @see SearchEngine::setShowSuggestion
	 *
	 * {@inheritDoc}
	 */
	public function setShowSuggestion( $showSuggestion ) {
		parent::setShowSuggestion( $showSuggestion );
		$this->fallbackSearchEngine->setShowSuggestion( $showSuggestion );
	}

	/**
	 * @see SearchEngine::completionSearchBackend
	 *
	 * {@inheritDoc}
	 */
	protected function completionSearchBackend( $search ) {

		$this->extendedSearch->setNamespaces(
			$this->namespaces
		);

		// Avoid MW's auto formatting of title entities
		if ( $search !== '' ) {
			$search[0] = strtolower( $search[0] );
		}

		return $this->extendedSearch->completionSearch( $search );
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getErrors() {
		return $this->extendedSearch->getErrors();
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getQueryString() {
		return $this->extendedSearch->getQueryString();
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getQueryLink() {
		return $this->extendedSearch->getQueryLink();
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @return boolean
	 */
	public function getShowSuggestion() {
		return $this->showSuggestion;
	}

}
