<?php

namespace SMW\Tests;

/**
 * @covers \SMW\DIConcept
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @license GNU GPL v2+
 * @author mwjames
 */
class DIConceptTest extends DataItemTest {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return 'SMW\DIConcept';
	}

	/**
	 * @see DataItemTest::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return array(
			array( 'Foo', '', '', '', '' ),
		);
	}

	/**
	 * @test DIConcept::setCacheStatus
	 * @test DIConcept::setCacheDate
	 * @test DIConcept::setCacheCount
	 * @dataProvider conceptCacheDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $status
	 * @param $date
	 * @param $count
	 */
	public function testConceptCacheSetterGetter( $status, $date, $count ) {

		$reflector = new \ReflectionClass( $this->getClass() );
		$instance  = $reflector->newInstanceArgs( array ( 'Foo', '', '', '', '' ) );

		$instance->setCacheStatus( $status );
		$instance->setCacheDate( $date );
		$instance->setCacheCount( $count );

		$this->assertEquals( $status, $instance->getCacheStatus() );
		$this->assertEquals( $date, $instance->getCacheDate() );
		$this->assertEquals( $count, $instance->getCacheCount() );

	}

	/**
	 * Data provider for testing concept cache setter/getter
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function conceptCacheDataProvider() {
		return array(
			array( 'empty', '', '' ),
			array( 'full', '1358515326', '1000' ),
		);
	}

}
