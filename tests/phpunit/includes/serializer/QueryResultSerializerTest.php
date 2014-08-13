<?php

namespace SMW\Test;

use SMW\Serializers\QueryResultSerializer;
use SMWQueryProcessor;
use SMWQueryResult;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\Serializers\QueryResultSerializer
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class QueryResultSerializerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Serializers\QueryResultSerializer';
	}

	/**
	 * Helper method that returns a QueryResultSerializer object
	 *
	 * @since 1.9
	 */
	private function newSerializerInstance() {
		return new QueryResultSerializer();
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newSerializerInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testSerializeOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newSerializerInstance();
		$instance->serialize( 'Foo' );

	}

	/**
	 * @dataProvider numberDataProvider
	 *
	 * @since  1.9
	 */
	public function testQueryResultSerializerOnMock( $setup, $expected ) {

		$results = $this->newSerializerInstance()->serialize( $setup['queryResult'] );

		$this->assertInternalType( 'array' , $results );
		$this->assertEquals( $expected['printrequests'], $results['printrequests'] );

	}

	/**
	 * @since  1.9
	 */
	public function testQueryResultSerializerOnMockOnDIWikiPageNonTitle() {

		$dataItem = $this->newMockBuilder()->newObject( 'DataItem', array(
			'getDIType' => DataItem::TYPE_WIKIPAGE,
			'getTitle'  => null
		) );

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'getPrintRequests'  => array(),
			'getResults'        => array( $dataItem ),
		) );

		$results = $this->newSerializerInstance()->serialize( $queryResult );

		$this->assertInternalType( 'array' , $results );
		$this->assertEmpty( $results['printrequests'] );
		$this->assertEmpty( $results['results'] );

	}

	/**
	 * @return array
	 */
	public function numberDataProvider() {

		$provider = array();

		$setup = array(
			array( 'printRequest' => 'Foo-1', 'typeId' => '_num', 'number' => 10, 'dataValue' => 'Quuey' ),
			array( 'printRequest' => 'Foo-2', 'typeId' => '_num', 'number' => 20, 'dataValue' => 'Vey' ),
		);

		$provider[] = array(
			array(
				'queryResult' => $this->buildMockQueryResult( $setup )
				),
			array(
				'printrequests' => array(
					array( 'label' => 'Foo-1', 'typeid' => '_num', 'mode' => 2, 'format' => false ),
					array( 'label' => 'Foo-2', 'typeid' => '_num', 'mode' => 2, 'format' => false )
				),
			)
		);

		return $provider;
	}

	/**
	 * @return QueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$printRequests = array();
		$resultArray   = array();
		$getResults    = array();

		foreach ( $setup as $value ) {

			$printRequest = $this->newMockBuilder()->newObject( 'PrintRequest', array(
				'getText'   => $value['printRequest'],
				'getLabel'  => $value['printRequest'],
				'getTypeID' => $value['typeId'],
				'getOutputFormat' => false
			) );

			$printRequests[] = $printRequest;
			$getResults[] = \SMW\DIWikipage::newFromTitle( $this->newTitle( NS_MAIN, $value['printRequest'] ) );

			$dataItem = $this->newMockBuilder()->newObject( 'DataItem', array(
				'getDIType' => DataItem::TYPE_NUMBER,
				'getNumber' => $value['number']
			) );

			$dataValue = $this->newMockBuilder()->newObject( 'DataValue', array(
				'DataValueType'    => 'SMWNumberValue',
				'getTypeID'        => '_num',
				'getShortWikiText' => $value['dataValue'],
				'getDataItem'      => $dataItem
			) );

			$resultArray[] = $this->newMockBuilder()->newObject( 'ResultArray', array(
				'getText'          => $value['printRequest'],
				'getPrintRequest'  => $printRequest,
				'getNextDataValue' => $dataValue,
				'getNextDataItem'  => $dataItem,
				'getContent'       => $dataItem
			) );

		}

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'getPrintRequests'  => $printRequests,
			'getNext'           => $resultArray,
			'getResults'        => $getResults,
			'getStore'          => $this->newMockBuilder()->newObject( 'Store' ),
			'getLink'           => new \SMWInfolink( true, 'Lala' , 'Lula' ),
			'hasFurtherResults' => true
		) );

		return $queryResult;
	}

}
