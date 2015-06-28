<?php

namespace SMW;

use MWNamespace;

/**
 * Examines if a specific namespace is enabled for the usage of the
 * Semantic MediaWiki extension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceExaminer {

	/** @var array */
	private static $instance = null;

	/** @var array */
	private $registeredNamespaces = array();

	/**
	 * @since 1.9
	 *
	 * @param array $registeredNamespaces
	 */
	public function __construct( array $registeredNamespaces ) {
		$this->registeredNamespaces = $registeredNamespaces;
	}

	/**
	 * Returns a static instance with an invoked global settings array
	 *
	 * @par Example:
	 * @code
	 *  \SMW\NamespaceExaminer::getInstance()->isSemanticEnabled( NS_MAIN )
	 * @endcode
	 *
	 * @note Used in smwfIsSemanticsProcessed
	 *
	 * @since 1.9
	 *
	 * @return NamespaceExaminer
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = self::newFromArray( Settings::newFromGlobals()->get( 'smwgNamespacesWithSemanticLinks' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers an array of available namespaces
	 *
	 * @par Example:
	 * @code
	 *  \SMW\NamespaceExaminer::newFromArray( array( ... ) )->isSemanticEnabled( NS_MAIN )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return NamespaceExaminer
	 */
	public static function newFromArray( $registeredNamespaces ) {
		return new self( $registeredNamespaces );
	}

	/**
	 * Resets static instance
	 *
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * Returns if a namespace is enabled for semantic processing
	 *
	 * @since 1.9
	 *
	 * @param integer $namespace
	 *
	 * @return boolean
	 * @throws InvalidNamespaceException
	 */
	public function isSemanticEnabled( $namespace ) {

		if ( !is_int( $namespace ) ) {
			throw new InvalidNamespaceException( "{$namespace} is not a number" );
		}

		if ( !in_array( $namespace, MWNamespace::getValidNamespaces() ) ) {
			// Bug 51435
			return false;
		}

		return $this->isEnabled( $namespace );
	}

	/**
	 * Asserts if a namespace is enabled
	 *
	 * @since 1.9
	 *
	 * @param integer $namespace
	 *
	 * @return boolean
	 */
	protected function isEnabled( $namespace ) {
		return !empty( $this->registeredNamespaces[$namespace] );
	}

}
