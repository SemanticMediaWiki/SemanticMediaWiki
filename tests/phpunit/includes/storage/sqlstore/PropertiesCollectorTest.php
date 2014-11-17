<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertiesCollector;

use SMW\Test\SemanticMediaWikiTestCase;

use SMW\MessageFormatter;
use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\Settings;

use SMWStringCondition;
use SMWRequestOptions;

use FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\PropertiesCollector
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
class PropertiesCollectorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\PropertiesCollector';
	}

	/**
	 * @since 1.9
	 *
	 * @return Database
	 */
	private function getMockDBConnection( $smwTitle = 'Foo', $smwId, $usageCount = 1 ) {

		$result = array(
			'smw_title'   => $smwTitle,
			'smw_id'      => $smwId,
			'p_id'        => (string)$smwId, // PropertyStatisticsTable assert ctype_digit
			'usage_count' => (string)$usageCount // PropertyStatisticsTable assert ctype_digit
		);

		$resultWrapper = new FakeResultWrapper( array( (object)$result ) );

		$databaseBase = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'select' ) )
			->getMockForAbstractClass();

		$databaseBase->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		return $databaseBase;
	}

	/**
	 * @since 1.9
	 *
	 * @return PropertiesCollector
	 */
	private function newInstance( $smwTitle = 'Foo', $usageCount = 1, $cacheEnabled = false ) {

		$id = 9999;

		$row = new \stdClass;
		$row->p_id = (string)$id; // PropertyStatisticsTable assert ctype_digit
		$row->usage_count = (string)$usageCount; // PropertyStatisticsTable assert ctype_digit

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getIdTable' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getIdTable' )
			->will( $this->returnValue( 'foo' ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$connection = $this->getMockDBConnection( $smwTitle, $id, $usageCount );

		$settings = Settings::newFromArray( array(
			'smwgCacheType'             => 'hash',
			'smwgPropertiesCache'       => $cacheEnabled,
			'smwgPropertiesCacheExpiry' => 360,
		) );

		return new PropertiesCollector( $store, $connection, $settings );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetResults() {

		$property = $this->newRandomString();
		$count    = rand();
		$expected = array( new DIProperty( $property ), $count );

		$instance = $this->newInstance( $property, $count );
		$requestOptions = new SMWRequestOptions();
		$requestOptions->limit = 1;
		$requestOptions->addStringCondition( $property, SMWStringCondition::STRCOND_MID  );

		$instance->setRequestOptions( $requestOptions );

		$this->assertEquals( array( $expected ), $instance->getResults() );
		$this->assertEquals( 1, $instance->getCount() );

	}

	/**
	 * @dataProvider exceptionDataProvider
	 *
	 * InvalidPropertyException is thrown but caught and is retuned as a
	 * SMWDIError object instead
	 *
	 * @since 1.9
	 */
	public function testInvalidPropertyException( $property ) {

		$instance = $this->newInstance( $property );
		$results  = $instance->getResults();

		$this->assertInternalType( 'array', $results );
		$this->assertEquals( 1, $instance->getCount() );
		$this->assertInstanceOf( 'SMWDIError', $results[0][0] );
		$this->assertContains(
			$property,
			MessageFormatter::newFromArray( $this->getLanguage(), array( $results[0][0]->getErrors() ) )->getHtml()
		);

	}

	/**
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 */
	public function testCacheNoCache( array $test, array $expected, array $info ) {

		// Sample A
		$instance = $this->newInstance(
			$test['A']['property'],
			$test['A']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->newInstance(
			$test['B']['property'],
			$test['B']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['B'], $instance->getResults(), $info['msg'] );
		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );
	}

	/**
	 * @return array
	 */
	public function exceptionDataProvider() {
		return array( array( '-Lala' ), array( '_Lila' ) );
	}

	/**
	 * @return array
	 */
	public function getCacheNonCacheDataProvider() {
		$propertyA = $this->newRandomString();
		$propertyB = $this->newRandomString();
		$countA = rand();
		$countB = rand();

		return array(
			array(

				// #0 Cached
				array(
					'cacheEnabled' => true,
					'A' => array( 'property' => $propertyA, 'count' => $countA ),
					'B' => array( 'property' => $propertyB, 'count' => $countB ),
				),
				array(
					'A' => array( array( new DIProperty( $propertyA ), $countA ) ),
					'B' => array( array( new DIProperty( $propertyA ), $countA ) )
				),
				array( 'msg' => 'Failed asserting that A & B are identical for a cached result' )
			),
			array(

				// #1 Non-cached
				array(
					'cacheEnabled' => false,
					'A' => array( 'property' => $propertyA, 'count' => $countA ),
					'B' => array( 'property' => $propertyB, 'count' => $countB )
				),
				array(
					'A' => array( array( new DIProperty( $propertyA ), $countA ) ),
					'B' => array( array( new DIProperty( $propertyB ), $countB ) )
				),
				array( 'msg' => 'Failed asserting that A & B are not identical for a non-cached result' )
			)
		);
	}
}
