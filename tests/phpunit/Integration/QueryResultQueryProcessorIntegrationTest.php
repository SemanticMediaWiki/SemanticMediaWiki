<?php

namespace SMW\Tests\Integration;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\DataValueFactory;

use SMWQueryProcessor  as QueryProcessor;
use SMWQuery as Query;
use SMWQueryParser as QueryParser;

/**
 * @covers \SMWQueryResult
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @author mwjames
 */
class QueryResultQueryProcessorIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;

	private $dataValueFactory;
	private $queryResultValidator;

	private $pageCreator;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();
		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();
		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->queryParser = new QueryParser();
	}

	public function testUriQueryFromRawParameters() {

		$this->pageCreator
			->createPage( \Title::newFromText( 'SomeUriValue', SMW_NS_PROPERTY ) )
			->doEdit( '[[Has type::URL]]' );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$dataValue = $this->dataValueFactory->newPropertyValue(
			'SomeUriValue',
			'http://example.org/api.php?action=Foo'
		);

		$semanticData->addDataValue( $dataValue );

		$dataValue = $this->dataValueFactory->newPropertyValue(
			'SomeUriValue',
			'http://example.org/Bar 42'
		);

		$semanticData->addDataValue( $dataValue );

		$this->getStore()->updateData( $semanticData );

		/**
		 * @query [[SomeUriValue::http://example.org/api.php?action=Foo]][[SomeUriValue::http://example.org/Bar 42]]
		 */
		$rawParams = array(
			'[[SomeUriValue::http://example.org/api.php?action=Foo]][[SomeUriValue::http://example.org/Bar 42]]',
			'?SomeUriValue',
			'limit=1'
		);

		list( $queryString, $parameters, $printouts ) = QueryProcessor::getComponentsFromFunctionParams(
			$rawParams,
			false
		);

		$description = $this->queryParser->getQueryDescription( $queryString );

		$query = new Query(
			$description,
			false,
			false
		);

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$semanticData->getSubject(),
			$queryResult
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testCanConstructor( array $test ) {

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$this->getQueryResultFor( $test['query'] )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testToArray( array $test, array $expected ) {

		$instance = $this->getQueryResultFor( $test['query'] );
		$results  = $instance->toArray();

		$this->assertEquals( $expected[0], $results['printrequests'][0] );
		$this->assertEquals( $expected[1], $results['printrequests'][1] );
	}

	private function getQueryResultFor( $queryString ) {

		list( $query, $formattedParams ) = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$queryString,
			SMW_OUTPUT_WIKI,
			QueryProcessor::INLINE_QUERY,
			false
		);

		return $this->getStore()->getQueryResult( $query );
	}

	public function queryDataProvider() {

		$provider = array();

		// #1 Standard query
		$provider[] =array(
			array( 'query' => array(
				'[[Modification date::+]]',
				'?Modification date',
				'limit=10'
				)
			),
			array(
				array(
					'label'=> '',
					'typeid' => '_wpg',
					'mode' => 2,
					'format' => false
				),
				array(
					'label'=> 'Modification date',
					'typeid' => '_dat',
					'mode' => 1,
					'format' => ''
				)
			)
		);

		// #2 Query containing a printrequest formatting
		$provider[] =array(
			array( 'query' => array(
				'[[Modification date::+]]',
				'?Modification date#ISO',
				'limit=10'
				)
			),
			array(
				array(
					'label'=> '',
					'typeid' => '_wpg',
					'mode' => 2,
					'format' => false
				),
				array(
					'label'=> 'Modification date',
					'typeid' => '_dat',
					'mode' => 1,
					'format' => 'ISO'
				)
			)
		);

		return $provider;
	}

}
