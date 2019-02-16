<?php

namespace SMW\SQLStore\Lookup;

use SMW\SQLStore\RedirectStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MissingRedirectLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var array
	 */
	private $namespaces;

	/**
	 * @var boolean
	 */
	private $nosort = false;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $namesspaces
	 */
	public function setNamespaceMatrix( array $namespaces ) {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 3.1
	 */
	public function noSort() {
		$this->nosort = true;
	}

	/**
	 * @since 3.1
	 *
	 * @return Iterator/array
	 */
	public function findMissingRedirects() {

		$namespaces = array_keys( array_filter( $this->namespaces, function( $v ) {
			return $v; }
		) );

		return $this->fetchFromTable( $namespaces );
	}

	private function fetchFromTable( $namespaces ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$options = [
			'ORDER BY' => 'page_namespace,page_title'
		];

		if ( $this->nosort ) {
			unset( $options['ORDER BY'] );
		}

		$rows = $connection->select(
			[ 'page', RedirectStore::TABLE_NAME ],
			[ 'page_id', 'page_title', 'page_namespace' ],
			[
				'page_is_redirect' => 1,
				'page_namespace' => $namespaces,
				's_title IS NULL'
			],
			__METHOD__,
			$options,
			[
				RedirectStore::TABLE_NAME => [
					'LEFT JOIN', [ "s_title=page_title", "s_namespace=page_namespace" ]
				]
			]
		);

		return $rows;
	}

}
