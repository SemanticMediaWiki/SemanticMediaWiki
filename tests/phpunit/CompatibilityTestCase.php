<?php

namespace SMW\Test;

/**
 * Compatibility layer to pass 1.19.7 tests
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Compatibility layer to pass 1.19.7 tests
 *
 * @ingroup test
 */
abstract class CompatibilityTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Does an associative sort that works for objects.
	 *
	 * @since 1.20
	 *
	 * @param array $array
	 */
	protected function objectAssociativeSort( array &$array ) {
		uasort(
			$array,
			function ( $a, $b ) {
				return serialize( $a ) > serialize( $b ) ? 1 : -1;
			}
		);
	}

	/**
	 * Assert that two arrays are equal
	 *
	 * @note Copied in order to pass MW 1.19.7 tests
	 *
	 * @see MediaWikiTestCase::assertArrayEquals
	 *
	 * @since  1.9
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param boolean $ordered If the order of the values should match
	 * @param boolean $named If the keys should match
	 */
	protected function assertArrayEquals( array $expected, array $actual, $ordered = false, $named = false ) {
		if ( !$ordered ) {
			$this->objectAssociativeSort( $expected );
			$this->objectAssociativeSort( $actual );
		}

		if ( !$named ) {
			$expected = array_values( $expected );
			$actual = array_values( $actual );
		}

		call_user_func_array(
			array( $this, 'assertEquals' ),
			array_merge( array( $expected, $actual ), array_slice( func_get_args(), 4 ) )
		);
	}

	/**
	 * Utility method taking an array of elements and wrapping
	 * each element in it's own array. Useful for data providers
	 * that only return a single argument.
	 *
	 * @see MediaWikiTestCase::arrayWrap
	 *
	 * @since 1.9
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

	/**
	 * Asserts that the provided variable is of the specified
	 * internal type or equals the $value argument. This is useful
	 * for testing return types of functions that return a certain
	 * type or *value* when not set or on error.
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 * @param mixed $actual
	 * @param mixed $value
	 * @param string $message
	 */
	protected function assertTypeOrValue( $type, $actual, $value = false, $message = '' ) {
		if ( $actual === $value ) {
			$this->assertTrue( true, $message );
		} else {
			$this->assertType( $type, $actual, $message );
		}
	}

	/**
	 * Asserts the type of the provided value. This can be either
	 * in internal type such as boolean or integer, or a class or
	 * interface the value extends or implements.
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 * @param mixed $actual
	 * @param string $message
	 */
	protected function assertType( $type, $actual, $message = '' ) {
		if ( class_exists( $type ) || interface_exists( $type ) ) {
			$this->assertInstanceOf( $type, $actual, $message );
		} else {
			$this->assertInternalType( $type, $actual, $message );
		}
	}
}
