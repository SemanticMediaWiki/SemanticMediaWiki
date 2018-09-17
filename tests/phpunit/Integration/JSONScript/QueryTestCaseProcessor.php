<?php

namespace SMW\Tests\Integration\JSONScript;

use SMW\Query\Parser as QueryParser;
use SMW\Store;
use SMWQuery as Query;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryParser
	 */
	private $fileReader;

	/**
	 * @var NumberValidator
	 */
	private $numberValidator;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store, $queryResultValidator, $stringValidator, $numberValidator ) {
		$this->store = $store;
		$this->queryResultValidator = $queryResultValidator;
		$this->stringValidator = $stringValidator;
		$this->numberValidator = $numberValidator;
	}

	/**
	 * @since  2.2
	 *
	 * @param QueryParser $queryParser
	 */
	public function setQueryParser( QueryParser $queryParser ) {
		$this->queryParser = $queryParser;
	}

	/**
	 * @since  2.2
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since  2.2
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	/**
	 * @since  2.2
	 *
	 * @param QueryTestCaseInterpreter $queryTestCaseInterpreter
	 */
	public function processQueryCase( QueryTestCaseInterpreter $queryTestCaseInterpreter ) {

		if ( !$queryTestCaseInterpreter->hasCondition() ) {
			$this->markTestSkipped( 'Found no condition for ' . $queryTestCaseInterpreter->isAbout() );
		}

		$description = $this->queryParser->getQueryDescription(
			$queryTestCaseInterpreter->getCondition()
		);

		$this->printDescriptionToOutput(
			$queryTestCaseInterpreter->isAbout(),
			$description
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = $queryTestCaseInterpreter->getQueryMode();
		$query->setLimit( $queryTestCaseInterpreter->getLimit() );

		$query->setOffset( $queryTestCaseInterpreter->getOffset() );
		$query->setExtraPrintouts( $queryTestCaseInterpreter->getExtraPrintouts() );

		$query->setSortKeys( $queryTestCaseInterpreter->getSortKeys() );
		$query->setContextPage( $queryTestCaseInterpreter->getSubject() );

		if ( $queryTestCaseInterpreter->isRequiredToClearStoreCache() ) {
			$this->getStore()->clear();
		}

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->printQueryResultToOutput( $queryResult );

		if ( is_string( $queryResult ) ) {
			return;
		}

		$this->assertEquals(
			$queryTestCaseInterpreter->getExpectedCount(),
			$queryResult->getCount(),
			'Failed asserting query result count on ' . $queryTestCaseInterpreter->isAbout()
		);

		if ( $queryTestCaseInterpreter->getExpectedErrorCount() > -1 ) {
			$this->numberValidator->assertThatCountComparesTo(
				$queryTestCaseInterpreter->getExpectedErrorCount(),
				$queryResult->getErrors(),
				'Failed asserting error count ' . $queryTestCaseInterpreter->isAbout()
			);
		}

		if ( $queryTestCaseInterpreter->getExpectedErrorCount() > 0 ) {
			return null;
		}

		if ( $queryTestCaseInterpreter->isFromCache() !== null ) {
			$this->assertEquals(
				$queryTestCaseInterpreter->isFromCache(),
				$queryResult->isFromCache(),
				'Failed asserting isFromCache for ' . $queryTestCaseInterpreter->isAbout()
			);
		}

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$queryTestCaseInterpreter->getExpectedSubjects(),
			$queryResult,
			$queryTestCaseInterpreter->isAbout()
		);

		$this->queryResultValidator->assertThatDataItemIsSet(
			$queryTestCaseInterpreter->getExpectedDataItems(),
			$queryResult,
			$queryTestCaseInterpreter->isAbout()
		);

		$this->queryResultValidator->assertThatDataValueIsSet(
			$queryTestCaseInterpreter->getExpectedDataValues(),
			$queryResult,
			$queryTestCaseInterpreter->isAbout()
		);
	}

	/**
	 * @since  2.2
	 *
	 * @param QueryTestCaseInterpreter $queryTestCaseInterpreter
	 */
	public function processConceptCase( QueryTestCaseInterpreter $queryTestCaseInterpreter ) {

		if ( !$queryTestCaseInterpreter->hasCondition() ) {
			$this->markTestSkipped( 'Found no condition for ' . $queryTestCaseInterpreter->isAbout() );
		}

		$description = $this->queryParser->getQueryDescription(
			$queryTestCaseInterpreter->getCondition()
		);

		$this->printDescriptionToOutput( $queryTestCaseInterpreter->isAbout(), $description );

		$query = new Query(
			$description,
			Query::CONCEPT_DESC
		);

		$query->querymode = $queryTestCaseInterpreter->getQueryMode();
		$query->setLimit( $queryTestCaseInterpreter->getLimit() );
		$query->setOffset( $queryTestCaseInterpreter->getOffset() );

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->printQueryResultToOutput( $queryResult );

		$this->assertEquals(
			$queryTestCaseInterpreter->getExpectedCount(),
			$queryResult->getCount(),
			'Failed asserting query result count on ' . $queryTestCaseInterpreter->isAbout()
		);

		if ( $queryTestCaseInterpreter->getExpectedErrorCount() > -1 ) {
			$this->numberValidator->assertThatCountComparesTo(
				$queryTestCaseInterpreter->getExpectedErrorCount(),
				$queryResult->getErrors(),
				'Failed asserting error count ' . $queryTestCaseInterpreter->isAbout()
			);
		}

		foreach ( $queryTestCaseInterpreter->getExpectedConceptCache() as $expectedConceptCache ) {

			$concept = Title::newFromText( $expectedConceptCache['concept'], SMW_NS_CONCEPT );

			$this->getStore()->refreshConceptCache( $concept );

			$this->assertEquals(
				$expectedConceptCache['count'],
				$this->getStore()->getConceptCacheStatus( $concept )->getCacheCount(),
				'Failed asserting conceptcache count on ' . $queryTestCaseInterpreter->isAbout()
			);
		}
	}

	/**
	 * @since  2.2
	 *
	 * @param QueryTestCaseInterpreter $queryTestCaseInterpreter
	 */
	public function processFormatCase( QueryTestCaseInterpreter $queryTestCaseInterpreter ) {

		if ( $queryTestCaseInterpreter->fetchTextFromOutputSubject() === '' ) {
			$this->markTestSkipped( 'No content found for ' . $queryTestCaseInterpreter->isAbout() );
		}

		$textOutput = $queryTestCaseInterpreter->fetchTextFromOutputSubject();

		$this->stringValidator->assertThatStringContains(
			$queryTestCaseInterpreter->getExpectedFormatOuputFor( 'to-contain' ),
			$textOutput,
			$queryTestCaseInterpreter->isAbout()
		);
	}

	private function printDescriptionToOutput( $about, $description ) {

		if ( !$this->debug ) {
			return;
		}

		print_r( $about . "\n" );
		print_r( $description );
	}

	private function printQueryResultToOutput( $queryResult ) {

		if ( is_string( $queryResult ) ) {
			return print_r( str_replace( [ "&#x0020;", "&#x003A;" ], [ " ", ":" ], $queryResult ) );
		}

		if ( !$this->debug ) {
			return;
		}

		print_r( 'QueryResult' . "\n" );
		print_r( implode( ',', $queryResult->getQuery()->getErrors() ) );
		print_r( implode( ',', $queryResult->getErrors() ) );
		print_r( $queryResult->toArray() );
	}

}
