<?php

namespace SMW\Tests\DataItems;

use SMW\DataItems\Concept;

/**
 * @covers \SMW\DataItems\Concept
 *
 * @group SMW
 * @group SMWExtension
 * @group DataItems
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author mwjames
 */
class ConceptTest extends AbstractDataItem {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return Concept::class;
	}

	/**
	 * @see AbstractDataItem::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return [
			[ 'Foo', '', '', '', '' ],
		];
	}

	/**
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
		$instance  = $reflector->newInstanceArgs( [ 'Foo', '', '', '', '' ] );

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
		return [
			[ 'empty', '', '' ],
			[ 'full', '1358515326', '1000' ],
		];
	}

}
