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
class QueryDefinitionTestCaseProcessor extends \PHPUnit_Framework_TestCase {

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
	 * @param QueryDefinitionInterpreter $queryDefinitionInterpreter
	 */
	public function processQueryDefinition( QueryDefinitionInterpreter $queryDefinitionInterpreter ) {

		if ( !$queryDefinitionInterpreter->hasCondition() ) {
			$this->markTestSkipped( 'Found no condition for ' . $queryDefinitionInterpreter->isAbout() );
		}

		$description = $this->queryParser->getQueryDescription(
			$queryDefinitionInterpreter->getCondition()
		);

		$this->printDescriptionToOutput(
			$queryDefinitionInterpreter->isAbout(),
			$description
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = $queryDefinitionInterpreter->getQueryMode();
		$query->setLimit( $queryDefinitionInterpreter->getLimit() );
		$query->setOffset( $queryDefinitionInterpreter->getOffset() );
		$query->setExtraPrintouts( $queryDefinitionInterpreter->getExtraPrintouts() );

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->printQueryResultToOutput( $queryResult );

		$this->assertEquals(
			$queryDefinitionInterpreter->getExpectedCount(),
			$queryResult->getCount(),
			'Failed asserting query result count on ' . $queryDefinitionInterpreter->isAbout()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$queryDefinitionInterpreter->getExpectedSubjects(),
			$queryResult
		);

		$this->queryResultValidator->assertThatDataItemIsSet(
			$queryDefinitionInterpreter->getExpectedDataItems(),
			$queryResult
		);

		$this->queryResultValidator->assertThatDataValueIsSet(
			$queryDefinitionInterpreter->getExpectedDataValues(),
			$queryResult
		);
	}

	/**
	 * @since  2.2
	 *
	 * @param QueryDefinitionInterpreter $queryDefinitionInterpreter
	 */
	public function processConceptDefinition( QueryDefinitionInterpreter $queryDefinitionInterpreter ) {

		if ( !$queryDefinitionInterpreter->hasCondition() ) {
			$this->markTestSkipped( 'Found no condition for ' . $queryDefinitionInterpreter->isAbout() );
		}

		$description = $this->queryParser->getQueryDescription(
			$queryDefinitionInterpreter->getCondition()
		);

		$this->printDescriptionToOutput( $queryDefinitionInterpreter->isAbout(), $description );

		$query = new Query(
			$description,
			false,
			true
		);

		$query->querymode = $queryDefinitionInterpreter->getQueryMode();
		$query->setLimit( $queryDefinitionInterpreter->getLimit() );
		$query->setOffset( $queryDefinitionInterpreter->getOffset() );

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->printQueryResultToOutput( $queryResult );

		$this->assertEquals(
			$queryDefinitionInterpreter->getExpectedCount(),
			$queryResult->getCount(),
			'Failed asserting query result count on ' . $queryDefinitionInterpreter->isAbout()
		);

		foreach ( $queryDefinitionInterpreter->getExpectedConceptCache() as $expectedConceptCache ) {

			$concept = Title::newFromText( $expectedConceptCache['concept'], SMW_NS_CONCEPT );

			$this->getStore()->refreshConceptCache( $concept );

			$this->assertEquals(
				$expectedConceptCache['count'],
				$this->getStore()->getConceptCacheStatus( $concept )->getCacheCount(),
				'Failed asserting conceptcache count on ' . $queryDefinitionInterpreter->isAbout()
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
