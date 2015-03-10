<?php

namespace SMW\Tests\Integration\Query;

use SMW\Store;
use SMWQueryParser as QueryParser;
use SMWQuery as Query;
use Title;

/**
 * @group semantic-mediawiki-integration
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
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param QueryParser $queryParser
	 */
	public function __construct( Store $store, QueryParser $queryParser,  $queryResultValidator ) {
		$this->store = $store;
		$this->queryParser = $queryParser;
		$this->queryResultValidator = $queryResultValidator;
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
	public function processQueryDefinition( QueryTestCaseInterpreter $queryTestCaseInterpreter ) {

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

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->printQueryResultToOutput( $queryResult );

		$this->assertEquals(
			$queryTestCaseInterpreter->getExpectedCount(),
			$queryResult->getCount(),
			'Failed asserting query result count on ' . $queryTestCaseInterpreter->isAbout()
		);

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
	public function processConceptDefinition( QueryTestCaseInterpreter $queryTestCaseInterpreter ) {

		if ( !$queryTestCaseInterpreter->hasCondition() ) {
			$this->markTestSkipped( 'Found no condition for ' . $queryTestCaseInterpreter->isAbout() );
		}

		$description = $this->queryParser->getQueryDescription(
			$queryTestCaseInterpreter->getCondition()
		);

		$this->printDescriptionToOutput( $queryTestCaseInterpreter->isAbout(), $description );

		$query = new Query(
			$description,
			false,
			true
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

	private function printDescriptionToOutput( $about, $description ) {

		if ( !$this->debug ) {
			return;
		}

		print_r( $about . "\n" );
		print_r( $description );
	}

	private function printQueryResultToOutput( $queryResult ) {

		if ( !$this->debug ) {
			return;
		}

		print_r( 'QueryResult' . "\n" );
		print_r( implode( ',', $queryResult->getQuery()->getErrors() ) );
		print_r( implode( ',', $queryResult->getErrors() ) );
		print_r( $queryResult->toArray() );
	}

}
