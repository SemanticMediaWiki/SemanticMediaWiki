<?php

namespace SMW\MediaWiki\Search;

use SearchEngine;
use SMW\Exception\ClassNotFoundException;
use SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException;
use SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author  mwjames
 */
class SearchEngineFactory {

	/**
	 * @since 3.1
	 *
	 * @param mixed $connection Either IConnectionProvider (MW 1.41+) or IDatabase (MW 1.40)
	 *
	 * @return SearchEngine
	 * @throws SearchEngineInvalidTypeException
	 */
	public function newFallbackSearchEngine( $connection = null ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		if ( $connection === null ) {
			// For MW 1.41+, getConnectionManager()->getConnection() returns IConnectionProvider
			// For MW 1.40, it returns IDatabase
			$connection = $applicationFactory->getConnectionManager()->getConnection( DB_REPLICA );
		}

		$dbLoadBalancer = $applicationFactory->create( 'DBLoadBalancer' );

		$type = $settings->get( 'smwgFallbackSearchType' );
		$defaultSearchEngine = $applicationFactory->create( 'DefaultSearchEngineTypeForDB', $connection );

		if ( is_callable( $type ) ) {
			$fallbackSearchEngine = $type( $dbLoadBalancer );
		} elseif ( $type !== null && $this->isValidSearchDatabaseType( $type ) ) {
			$fallbackSearchEngine = new $type( $dbLoadBalancer );
		} else {
			$fallbackSearchEngine = new $defaultSearchEngine( $dbLoadBalancer );
		}

		if ( !$fallbackSearchEngine instanceof SearchEngine ) {
			throw new SearchEngineInvalidTypeException( "The fallback is not a valid search engine type." );
		}

		return $fallbackSearchEngine;
	}

	/**
	 * @since 3.1
	 *
	 * @param SearchEngine $fallbackSearchEngine
	 *
	 * @return ExtendedSearch
	 */
	public function newExtendedSearch( \SearchEngine $fallbackSearchEngine ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$searchEngineConfig = $applicationFactory->create( 'SearchEngineConfig' );

		$store = $applicationFactory->getStore();

		$extendedSearch = new ExtendedSearch(
			$store,
			$fallbackSearchEngine
		);

		$extendedSearch->setExtraPrefixMap(
			ProfileForm::getPrefixMap( ProfileForm::getFormDefinitions( $store ) )
		);

		$extendedSearch->setSearchableNamespaces(
			$searchEngineConfig->searchableNamespaces()
		);

		return $extendedSearch;
	}

	/**
	 * @param $type
	 */
	private function isValidSearchDatabaseType( $type ) {
		if ( !class_exists( $type ) ) {
			throw new ClassNotFoundException( "$type does not exist." );
		}

		if ( $type === 'SMWSearch' ) {
			throw new SearchEngineInvalidTypeException( 'SMWSearch is not a valid fallback search engine type.' );
		}

		if ( $type !== 'SearchEngine' && !is_subclass_of( $type, 'SearchDatabase' ) ) {
			throw new SearchDatabaseInvalidTypeException( $type );
		}

		return true;
	}

}
