<?php

namespace SMW\Tests\Utils\Validators;

use SMW\Query\QueryComparator;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class NumberValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @since 2.5
	 *
	 * @param integer|string $expected
	 * @param integer|string $actual
	 */
	public function assertThatNumberComparesTo( $expected, $actual, $message = '' ) {
		return $this->doAssertWith( $expected, $actual, $message );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|string $expected
	 * @param array $actual
	 */
	public function assertThatCountComparesTo( $expected, array $actual, $message = '' ) {
		return $this->doAssertWith( $expected, count( $actual ), $message );
	}

	private function doAssertWith( $expected, $actual, $message ) {

		$comparator = QueryComparator::getInstance()->extractComparatorFromString( $expected );

		$expected = (int)$expected;
		$actual = (int)$actual;

		if ( $comparator === SMW_CMP_EQ ) {
			return self::assertEquals( $expected, $actual, $message );
		}

		if ( $comparator === SMW_CMP_GRTR ) {
			return self::assertGreaterThan( $expected, $actual, $message );
		}

		if ( $comparator === SMW_CMP_GEQ ) {
			return self::assertGreaterThanOrEqual( $expected, $actual, $message );
		}

		if ( $comparator === SMW_CMP_LESS ) {
			return self::assertLessThan( $expected, $actual, $message );
		}

		if ( $comparator === SMW_CMP_LEQ ) {
			return self::assertLessThanOrEqual( $expected, $actual, $message );
		}
	}

}
