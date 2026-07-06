<?php

namespace SMW\MediaWiki\Search;

use MediaWiki\MediaWikiServices;
use SearchEngine;
use SMW\Exception\ClassNotFoundException;
use SMW\MediaWiki\Search\Exception\SearchDatabaseInvalidTypeException;
use SMW\MediaWiki\Search\Exception\SearchEngineInvalidTypeException;
use SMW\MediaWiki\Search\ProfileForm\ProfileForm;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Wikimedia\Rdbms\IConnectionProvider;

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
	 * @throws SearchEngineInvalidTypeException
	 */
	public function newFallbackSearchEngine( ?IConnectionProvider $connection = null ): SearchEngine {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		if ( $connection === null ) {
			$connection = MediaWikiServices::getInstance()->getConnectionProvider();
		}

		$dbLoadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

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
	public function newExtendedSearch( SearchEngine $fallbackSearchEngine ): ExtendedSearch {
		$applicationFactory = ApplicationFactory::getInstance();
		$searchEngineConfig = MediaWikiServices::getInstance()->getSearchEngineConfig();

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
	private function isValidSearchDatabaseType( $type ): bool {
		if ( !class_exists( $type ) ) {
			throw new ClassNotFoundException( "$type does not exist." );
		}

		if ( ExtendedSearchEngine::isActiveSearchType( $type ) ) {
			throw new SearchEngineInvalidTypeException( "$type is not a valid fallback search engine type." );
		}

		if ( $type !== 'SearchEngine' && !is_subclass_of( $type, 'SearchDatabase' ) ) {
			throw new SearchDatabaseInvalidTypeException( $type );
		}

		return true;
	}

}
