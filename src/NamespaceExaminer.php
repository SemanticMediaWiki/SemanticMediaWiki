<?php

namespace SMW;

use InvalidArgumentException;

/**
 * Examines if a specific namespace is enabled for the usage of the
 * Semantic MediaWiki extension
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class NamespaceExaminer {

	/**
	 * @var array
	 */
	private $registeredNamespaces = [];

	/**
	 * @var array
	 */
	private $validNamespaces = [];

	/**
	 * @since 1.9
	 *
	 * @param array $registeredNamespaces
	 */
	public function __construct( array $registeredNamespaces ) {
		$this->registeredNamespaces = $registeredNamespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $validNamespaces
	 */
	public function setValidNamespaces( array $validNamespaces ) {
		$this->validNamespaces = $validNamespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title|DIWikiPage $object
	 *
	 * @return bool
	 */
	public function inNamespace( $object ) {
		$namespace = null;

		if ( $object instanceof \Title ) {
			$namespace = $object->getNamespace();
		}

		if ( $object instanceof \SMW\DIWikiPage ) {
			$namespace = $object->getNamespace();
		}

		return $this->isSemanticEnabled( $namespace );
	}

	/**
	 * Returns if a namespace is enabled for semantic processing
	 *
	 * @since 1.9
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function isSemanticEnabled( $namespace ) {
		if ( !is_int( $namespace ) ) {
			throw new InvalidArgumentException( "{$namespace} is not a number" );
		}

		if ( !in_array( $namespace, $this->validNamespaces ) ) {
			// Bug 51435
			return false;
		}

		return $this->isEnabled( $namespace );
	}

	private function isEnabled( $namespace ) {
		return !empty( $this->registeredNamespaces[$namespace] );
	}

}
