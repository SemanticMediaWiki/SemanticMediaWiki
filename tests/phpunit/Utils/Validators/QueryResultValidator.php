<?php

namespace SMW\Tests\Utils\Validators;

use Closure;
use RuntimeException;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryResultValidator extends \PHPUnit_Framework_Assert {

	private $dataValueValidationMethod = null;

	/**
	 * @since 2.0
	 *
	 * @param mixed $expected
	 * @param QueryResult $queryResult
	 *
	 * @throws RuntimeException
	 */
	public function assertThatQueryResultContains( $expected, QueryResult $queryResult ) {

		if ( $expected instanceof DataValue ) {
			return $this->assertThatDataValueIsSet( $expected, $queryResult );
		}

		if ( $expected instanceof DataItem ) {
			return $this->assertThatDataItemIsSet( $expected, $queryResult );
		}

		throw new RuntimeException( "Expected object is unknown or not registered" );
	}

	/**
	 * @since 2.0
	 *
	 * @param $expected
	 * @param QueryResult $queryResult
	 * @param string $message
	 */
	public function assertThatDataValueIsSet( $expected, QueryResult $queryResult, $message = '' ) {

		$expected = is_array( $expected ) ? $expected : [ $expected ];

		if ( $expected === [] ) {
			return;
		}

		$errors = $queryResult->getErrors();

		$this->assertEmpty(
			$errors,
			"Failed on {$message} with error(s): " . implode( ',', $errors )
		);

		if ( $this->dataValueValidationMethod === null ) {
			$this->useWikiValueForDataValueValidation();
		}

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $dataValue = $result->getNextDataValue() ) !== false ) {
					foreach ( $expected as $key => $exp ) {
						if ( call_user_func_array( $this->dataValueValidationMethod, [ $exp, $dataValue ] ) ) {
							unset( $expected[$key] );
						}
					}
				}
			}
		}

		$this->assertEmpty(
			$expected,
			"Failed on on {$message} to match datavalues [ " . implode( ', ', $expected ) . ' ] against the expected results.'
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param mixed $expected
	 * @param QueryResult $queryResult
	 * @param string $message
	 */
	public function assertThatDataItemIsSet( $expected, QueryResult $queryResult, $message = '', $checkSorting = false ) {

		$expected = is_array( $expected ) ? $expected : [ $expected ];

		// Keep the key to allow comparing the position
		$clonedExpected = $expected;
		$sorting = [];

		if ( $expected === [] ) {
			return;
		}

		$errors = $queryResult->getErrors();

		$this->assertEmpty(
			$errors,
			"Failed on {$message} with error(s): " . implode( ',', $errors )
		);

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $k => $result ) {
				while ( ( $dataItem = $result->getNextDataItem() ) !== false ) {
					$sorting[] = $dataItem;
					foreach ( $expected as $key => $exp ) {
						if ( $exp->equals( $dataItem ) ) {
							unset( $expected[$key] );
						}
					}
				}
			}
		}

		if ( $checkSorting && $expected === [] ) {
			$sorting = array_diff_assoc( $sorting, $clonedExpected );
			$this->assertEmpty(
				$sorting,
				"Failed on {$message} to match sorting for [ " . implode( ', ', $sorting ) . ' ] against the expected results.'
			);
		}

		$this->assertEmpty(
			$expected,
			"Failed on {$message} to match dataItems [ " . implode( ', ', $expected ) . ' ] against the expected results.'
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param mixed $expectedSubjects
	 * @param QueryResult $queryResult
	 * @param string $message
	 */
	public function assertThatQueryResultHasSubjects( $expectedSubjects, QueryResult $queryResult, $message = '' ) {

		$expectedSubjects = is_array( $expectedSubjects ) ? $expectedSubjects : [ $expectedSubjects ];
		$expectedToCount  = count( $expectedSubjects );
		$actualComparedToCount = 0;

		$errors = $queryResult->getErrors();

		$this->assertEmpty(
			$errors,
			"Failed on {$message} with error(s): " . implode( ',', $errors )
		);

		if ( $expectedToCount == 0 ) {
			return;
		}

		$resultSubjects = $queryResult->getResults();

		foreach ( $resultSubjects as $rKey => $resultSubject ) {
			foreach ( $expectedSubjects as $ekey => $expectedSubject ) {

				if ( $expectedSubject instanceof DIWikiPage && $expectedSubject->equals( $resultSubject ) ) {
					$actualComparedToCount++;
					unset( $expectedSubjects[$ekey] );
					unset( $resultSubjects[$rKey] );
				}
			}
		}

		$this->assertEquals(
			$expectedToCount,
			$actualComparedToCount,
			"Failed on {$message} asserting that " . implode( ', ', $expectedSubjects ) . ' is set.'
		);

		$this->assertEmpty(
			$resultSubjects,
			"Failed on {$message} to match results [ " . implode( ', ', $resultSubjects ) . ' ] against the expected subjects.'
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param Closure $validationMethod
	 *
	 * @return QueryResultValidator
	 */
	public function registerCustomMethodForDataValueValidation( Closure $validationMethod ) {
		$this->dataValueValidationMethod = $validationMethod;
		return $this;
	}

	/**
	 * @since 2.0
	 *
	 * @return QueryResultValidator
	 */
	public function useWikiValueForDataValueValidation() {

		$this->dataValueValidationMethod = function( DataValue $expectedDataValue, DataValue $dataValue ) {
			return $expectedDataValue->getWikiValue() === $dataValue->getWikiValue();
		};

		return $this;
	}

}
