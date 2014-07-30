<?php

namespace SMW\Tests\Util;

use SMW\DIWikiPage;

use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMWQueryResult as QueryResult;

use Closure;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class QueryResultValidator extends \PHPUnit_Framework_Assert {

	private $dataValueValidationMethod = null;

	/**
	 * @since 2.0
	 *
	 * @param  mixed $expected
	 * @param  QueryResult $queryResult
	 *
	 * @throws RuntimeException
	 */
	public function assertThatQueryResultContains( $expected, QueryResult $queryResult ) {

		if ( $expected instanceOf DataValue ) {
			return $this->assertThatDataValueIsSet( $expected, $queryResult );
		}

		if ( $expected instanceOf DataItem ) {
			return $this->assertThatDataItemIsSet( $expected, $queryResult );
		}

		throw new RuntimeException( "Expected object is unknown or not registered" );
	}

	/**
	 * @since 2.0
	 *
	 * @param  DataValue $expectedDataValue
	 * @param  QueryResult $queryResult
	 */
	public function assertThatDataValueIsSet( DataValue $expectedDataValue, QueryResult $queryResult ) {

		if ( $this->dataValueValidationMethod === null ) {
			$this->useWikiValueForDataValueValidation();
		}

		$assertThatDataValueIsSet = false;
		$this->assertEmpty( $queryResult->getErrors() );

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $dataValue = $result->getNextDataValue() ) !== false ) {
					if ( call_user_func_array( $this->dataValueValidationMethod, array( $expectedDataValue, $dataValue ) ) ) {
						$assertThatDataValueIsSet = true;
					}
				}
			}
		}

		$this->assertTrue( $assertThatDataValueIsSet, 'Asserts that the expected DataValue is set' );
	}

	/**
	 * @since 2.0
	 *
	 * @param  DataItem $expectedDataItem
	 * @param  QueryResult $queryResult
	 */
	public function assertThatDataItemIsSet( DataItem $expectedDataItem, QueryResult $queryResult ) {

		$assertThatDataItemIsSet = false;
		$this->assertEmpty( $queryResult->getErrors() );

		while ( $resultArray = $queryResult->getNext() ) {
			foreach ( $resultArray as $result ) {
				while ( ( $dataItem = $result->getNextDataItem() ) !== false ) {
					$this->assertEquals( $expectedDataItem, $dataItem );
					$assertThatDataItemIsSet = true;
				}
			}
		}

		$this->assertTrue( $assertThatDataItemIsSet, 'Asserts that the expected DataItem is set'  );
	}

	/**
	 * @since 2.0
	 *
	 * @param  mixed $expected
	 * @param  QueryResult $queryResult
	 */
	public function assertThatQueryResultHasSubjects( $expectedSubjects, QueryResult $queryResult ) {

		$expectedSubjects = is_array( $expectedSubjects ) ? $expectedSubjects : array( $expectedSubjects );
		$expectedToCount  = count( $expectedSubjects );
		$actualComparedToCount = 0;

		$assertThatQueryResultHasSubjects = false;

		$this->assertEmpty( $queryResult->getErrors() );

		foreach ( $queryResult->getResults() as $resultSubject ) {
			foreach ( $expectedSubjects as $expectedSubject ) {

				if ( $expectedSubject instanceOf DIWikiPage && $expectedSubject->equals( $resultSubject ) ) {
					$actualComparedToCount++;
					$assertThatQueryResultHasSubjects = true;
				}
			}
		}

		if ( $expectedToCount > 0 ) {
			$this->assertTrue( $assertThatQueryResultHasSubjects, 'Asserts that a subject is set' );
			$this->assertEquals( $expectedToCount, $actualComparedToCount, 'Asserts that all listed subjects are set' );
		}
	}

	/**
	 * @since 2.0
	 *
	 * @param  Closure $validationMethod
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
