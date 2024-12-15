<?php

namespace SMW\Tests;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
trait PHPUnitCompat {

	/**
	 * @see \PHPUnit\Framework\TestCase::setExpectedException
	 */
	public function setExpectedException( $name, $message = '', $code = null ) {
		if ( is_callable( [ $this, 'expectException' ] ) ) {
			if ( $name !== null ) {
				$this->expectException( $name );
			}
			if ( $message !== '' ) {
				$this->expectExceptionMessage( $message );
			}
			if ( $code !== null ) {
				$this->expectExceptionCode( $code );
			}
		} else {
			parent::setExpectedException( $name, $message, $code );
		}
	}

	/**
	 * "Using assertContains() with string haystacks is deprecated and will not
	 * be supported in PHPUnit 9. Refactor your test to use assertStringContainsString()
	 * or assertStringContainsStringIgnoringCase() instead."
	 */
	public static function assertContains( $needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false ): void {
		if ( is_callable( [ '\PHPUnit\Framework\TestCase', 'assertStringContainsString' ] ) ) {
			if ( is_array( $haystack ) ) {
				$haystack = implode( ' ', $haystack );
			}
			parent::assertStringContainsString( $needle, $haystack );
		} else {
			parent::assertContains( $needle, $haystack );
		}
	}

	/**
	 * "UUsing assertNotContains() with string haystacks is deprecated and will
	 * not be supported in PHPUnit 9. Refactor your test to use assertStringNotContainsString()
	 * or assertStringNotContainsStringIgnoringCase() instead."
	 */
	public static function assertNotContains( $needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false ): void {
		if ( is_callable( [ '\PHPUnit\Framework\TestCase', 'assertStringNotContainsString' ] ) ) {
			if ( is_array( $haystack ) ) {
				$haystack = implode( ' ', $haystack );
			}
			parent::assertStringNotContainsString( $needle, $haystack );
		} else {
			parent::assertNotContains( $needle, $haystack );
		}
	}

	/**
	 * "assertInternalType() is deprecated and will be removed in PHPUnit 9.
	 * Refactor your test to use assertIsArray(), assertIsBool(), assertIsFloat(),
	 * assertIsInt(), assertIsNumeric(), assertIsObject(), assertIsResource(),
	 * assertIsString(), assertIsScalar(), assertIsCallable(), or assertIsIterable()
	 * instead."
	 */
	public static function assertInternalType( $expected, $actual, $message = '' ): void {
		if ( is_callable( [ '\PHPUnit\Framework\TestCase', 'assertIsArray' ] ) ) {
			if ( $expected === 'array' ) {
				parent::assertIsArray( $actual, $message );
			}

			if ( $expected === 'boolean' || $expected === 'bool' ) {
				parent::assertIsBool( $actual, $message );
			}

			if ( $expected === 'float' ) {
				parent::assertIsFloat( $actual, $message );
			}

			if ( $expected === 'integer' || $expected === 'int' ) {
				parent::assertIsInt( $actual, $message );
			}

			if ( $expected === 'numeric' ) {
				parent::assertIsNumeric( $actual, $message );
			}

			if ( $expected === 'object' ) {
				parent::assertIsObject( $actual, $message );
			}

			if ( $expected === 'string' ) {
				parent::assertIsString( $actual, $message );
			}
		} else {
			parent::assertInternalType( $expected, $actual, $message );
		}
	}
}
