<?php

namespace SMW\SQLStore\Lookup;

use SMW\SQLStore\RedirectStore;
use SMW\Store;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class MissingRedirectLookup {

	private ?array $namespaces = null;

	private bool $nosort = false;

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param array $namespaces
	 */
	public function setNamespaceMatrix( array $namespaces ): void {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 3.1
	 */
	public function noSort(): void {
		$this->nosort = true;
	}

	/**
	 * @since 3.1
	 *
	 * @return ResultWrapper
	 */
	public function findMissingRedirects() {
		$namespaces = array_keys( array_filter( $this->namespaces, static function ( $v ) {
			return $v;
		}
		) );

		return $this->fetchFromTable( $namespaces );
	}

	/**
	 * @param array $namespaces
	 *
	 * @return ResultWrapper
	 */
	private function fetchFromTable( array $namespaces ) {
		$connection = $this->store->getConnection( 'mw.db' );

		$queryBuilder = $connection->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_title', 'page_namespace' ] )
			->from( 'page' )
			->leftJoin( RedirectStore::TABLE_NAME, null, [ 's_title=page_title', 's_namespace=page_namespace' ] )
			->where( [
				'page_is_redirect' => 1,
				'page_namespace' => $namespaces,
				's_title IS NULL'
			] )
			->caller( __METHOD__ );

		if ( !$this->nosort ) {
			$queryBuilder->orderBy( 'page_namespace,page_title' );
		}

		return $queryBuilder->fetchResultSet();
	}

}
