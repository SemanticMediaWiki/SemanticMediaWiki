<?php

namespace SMW\Tests\Utils\Validators;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class StringValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @since 2.1
	 *
	 * @param mixed $expected
	 * @param string $actual
	 */
	public function assertThatStringContains( $expected, $actual, $message = '' ) {

		$callback = function( &$expected, $actual, &$actualCounted ) {
			foreach ( $expected as $key => $pattern ) {
				if ( $this->isMatch( $pattern, $actual ) ) {
					$actualCounted++;
					unset( $expected[$key] );
				}
			}
		};

		$this->doAssertWith( $expected, $actual, $message, 'StringContains', $callback );
	}

	/**
	 * @since 2.3
	 *
	 * @param mixed $expected
	 * @param string $actual
	 */
	public function assertThatStringNotContains( $expected, $actual, $message = '' ) {

		$callback = function( &$expected, $actual, &$actualCounted ) {
			foreach ( $expected as $key => $pattern ) {
				if ( $this->isMatch( $pattern, $actual ) === false ) {
					$actualCounted++;
					unset( $expected[$key] );
				}
			}
		};

		$this->doAssertWith( $expected, $actual, $message, 'StringNotContains', $callback );
	}

	private function doAssertWith( $expected, $actual, $message = '', $method = '', $callback ) {

		if ( !is_array( $expected ) ) {
			$expected = [ $expected ];
		}

		$expected = array_filter( $expected, 'strlen' );

		if ( $expected === [] ) {
			return self::assertTrue( true, $message );
		}

		self::assertInternalType(
			'string',
			$actual
		);

		$expectedToCount = count( $expected );
		$actualCounted = 0;

		call_user_func_array(
			$callback,
			[ &$expected, $actual, &$actualCounted ]
		);

		self::assertEquals(
			$expectedToCount,
			$actualCounted,
			"Failed \"{$message}\" for $method:\n==== (actual) ====\n$actual\n==== (expected) ====\n" . $this->toString( $expected )
		);
	}

	private function isMatch( $pattern, $source ) {

		// use /.../ indicator to use the preg_match search match
		if ( strlen( $pattern) >= 2 && substr( $pattern, 0, 1) === '/' && substr( $pattern, -1) === '/' ) {

			return (bool) preg_match( $pattern, $source );

		}

		// use .* indicator to use the wildcard search match
		if ( strpos( $pattern, '.*' ) !== false ) {

			$pattern = preg_quote( $pattern, '/' );
			$pattern = str_replace( '\.\*', '.*?', $pattern );

			return (bool) preg_match( '/' . $pattern . '/', $source );

		}

		// use a simple strpos (as it is faster)
		return strpos( $source, $pattern ) !== false;

	}

	private function toString( $expected ) {
		return "[ " . ( is_array( $expected ) ? implode( " ], [ ", $expected ) : $expected ) . " ]\n";
	}

}
