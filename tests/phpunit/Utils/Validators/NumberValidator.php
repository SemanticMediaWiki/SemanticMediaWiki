<?php

namespace SMW\Tests\Utils\Validators;

use SMW\Query\QueryComparator;

/**
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class NumberValidator extends \PHPUnit\Framework\Assert {

	/**
	 * @since 2.5
	 *
	 * @param int|string $expected
	 * @param int|string $actual
	 */
	public function assertThatNumberComparesTo( $expected, $actual, $message = '' ) {
		return $this->doAssertWith( $expected, $actual, $message );
	}

	/**
	 * @since 2.5
	 *
	 * @param int|string $expected
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
