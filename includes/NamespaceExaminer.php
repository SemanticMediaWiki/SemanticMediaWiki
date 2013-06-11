<?php

namespace SMW;

use MWNamespace;

/**
 * This Class examines if a specific namespace is enabled for the usage of the
 * Semantic MediaWiki extension
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * This class examines if a specific namespace is enabled for the usage of the
 * Semantic MediaWiki extension
 *
 * @ingroup SMW
 */
final class NamespaceExaminer {

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
	public static function reset() {
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
			throw new InvalidNamespaceException( "{$namespace} is not a valid namespace" );
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
