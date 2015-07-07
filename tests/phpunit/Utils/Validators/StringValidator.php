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
		$this->doAssertFor( $expected, $actual, $message, function( $actual, &$expected, &$actualCounted ) {
			foreach ( $expected as $key => $string ) {
				if ( strpos( $actual, $string ) !== false ) {
					$actualCounted++;
					unset( $expected[$key] );
				}
			}
		} );
	}

	/**
	 * @since 2.3
	 *
	 * @param mixed $expected
	 * @param string $actual
	 */
	public function assertThatStringNotContains( $expected, $actual, $message = '' ) {
		$this->doAssertFor( $expected, $actual, $message, function( $actual, &$expected, &$actualCounted ) {
			foreach ( $expected as $key => $string ) {
				if ( strpos( $actual, $string ) === false ) {
					$actualCounted++;
					unset( $expected[$key] );
				}
			}
		} );
	}

	private function doAssertFor( $expected, $actual, $message = '', $callback ) {

		if ( !is_array( $expected ) ) {
			$expected = array( $expected );
		}

		$expected = array_filter( $expected, 'strlen' );

		if ( $expected === array() ) {
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
			array( $actual, &$expected, &$actualCounted )
		);

		self::assertEquals(
			$expectedToCount,
			$actualCounted,
			"Failed on `{$message}` for $actual with " . $this->toString( $expected )
		);
	}

	private function toString( $expected ) {
		return "[ " . ( is_array( $expected ) ? implode( ', ', $expected ) : $expected ) . " ]";
	}

}
