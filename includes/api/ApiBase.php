<?php

namespace SMW;

/**
 * Semantic MediaWiki Api Base class
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class ApiBase extends \ApiBase implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context = null;

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
	}

	/**
	 * @see ContextInjector::invokeContext
	 *
	 * @since 1.9
	 *
	 * @param ContextResource
	 */
	public function invokeContext( ContextResource $context ) {
		$this->context = $context;
	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {

		if ( $this->context === null ) {
			$this->context = new BaseContext();
		}

		return $this->context;
	}

}
