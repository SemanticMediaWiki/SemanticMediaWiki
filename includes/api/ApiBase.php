<?php

namespace SMW;

/**
 * Semantic MediaWiki Api Base class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Semantic MediaWiki Api Base class
 *
 * @ingroup Api
 * @codeCoverageIgnore
 */
abstract class ApiBase extends \ApiBase implements StoreAccess {

	/** @var Store */
	protected $store = null;

	/**
	 * @see ApiBase::__construct
	 *
	 * @since 1.9
	 *
	 * @param ApiMain $main
	 * @param string $action Name of this module
	 */
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
		$this->store = StoreFactory::getStore();
	}

	/**
	 * Sets Store object
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
		return $this;
	}

	/**
	 * Returns Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore() {

		if ( $this->store === null ) {
			$this->store = StoreFactory::getStore();
		}

		return $this->store;
	}

}
