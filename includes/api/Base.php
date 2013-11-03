<?php

namespace SMW\Api;

use SMW\ContextInjector;
use SMW\ContextResource;
use SMW\ContextAware;
use SMW\ExtensionContext;

/**
 * Semantic MediaWiki Api Base class
 *
 * @ingroup Api
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class Base extends \ApiBase implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context = null;

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
			$this->context = new ExtensionContext();
		}

		return $this->context;
	}

}
