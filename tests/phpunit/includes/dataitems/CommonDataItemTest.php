<?php

namespace SMW\Tests;

/**
 * @covers \SMWDataItem
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class CommonDataItemTest extends \PHPUnit\Framework\TestCase {

	public function testSerializationToFilterSameStringRepresentation() {
		$items = [];

		foreach ( [ 'Foo', 'Bar', 'Foo' ] as  $value ) {

			$dataItem = $this->getMockBuilder( '\SMWDataItem' )
				->disableOriginalConstructor()
				->getMockForAbstractClass();

			$dataItem->expects( $this->any() )
				->method( 'getSerialization' )
				->willReturn( $value );

			$items[] = $dataItem;
		}

		$this->assertCount(
			2,
			array_unique( $items )
		);
	}

}
