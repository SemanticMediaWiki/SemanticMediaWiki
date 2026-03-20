<?php

namespace SMW\Tests\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\DataItem;
use SMW\DataItems\GeoCoord;
use SMW\DataModel\ContainerSemanticData;
use SMW\Exporter\Element;
use SMW\Exporter\ElementFactory;
use stdclass;

/**
 * @covers \SMW\Exporter\ElementFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ElementFactoryTest extends TestCase {

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testNewFromDataItemForSupportedTypes( $dataItem ) {
		$instance = new ElementFactory();

		$this->assertInstanceOf(
			Element::class,
			$instance->newFromDataItem( $dataItem )
		);
	}

	/**
	 * @dataProvider unsupportedDataItemProvider
	 */
	public function testUnsupportedDataItemTypes( $dataItem ) {
		$instance = new ElementFactory();

		$this->assertNull(
			$instance->newFromDataItem( $dataItem )
		);
	}

	public function testNotSupportedEncoderResultThrowsException() {
		$dataItemFactory = new DataItemFactory();
		$instance = new ElementFactory();

		$instance->registerCallableMapper( DataItem::TYPE_BLOB, static function ( $datatem ) {
			return new stdclass;
		} );

		$this->expectException( 'RuntimeException' );
		$instance->newFromDataItem( $dataItemFactory->newDIBlob( 'foo' ) );
	}

	public function supportedDataItemProvider() {
		$dataItemFactory = new DataItemFactory();

		# 0
		$provider[] = [
			$dataItemFactory->newDINumber( 42 )
		];

		# 1
		$provider[] = [
			$dataItemFactory->newDIBlob( 'Test' )
		];

		# 2
		$provider[] = [
			$dataItemFactory->newDIBoolean( true )
		];

		# 3
		$provider[] = [
			$dataItemFactory->newDIUri( 'http', '//example.org', '', '' )
		];

		# 4
		$provider[] = [
			$dataItemFactory->newDITime( 1, '1970' )
		];

		# 5
		$provider[] = [
			$dataItemFactory->newDIContainer( new ContainerSemanticData( $dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) )
		];

		# 6
		$provider[] = [
			$dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN )
		];

		# 7
		$provider[] = [
			$dataItemFactory->newDIProperty( 'Foo' )
		];

		# 8
		$provider[] = [
			$dataItemFactory->newDIConcept( 'Foo', '', '', '', '' )
		];

		return $provider;
	}

	public function unsupportedDataItemProvider() {
		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->setMethods( [ '__toString' ] )
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( '__toString' )
			->willReturn( 'Foo' );

		# 0
		$provider[] = [
			$dataItem
		];

		# 1
		$provider[] = [
			new GeoCoord( [ 'lat' => 52, 'lon' => 1 ] )
		];

		return $provider;
	}

}
