<?php

namespace SMW\Tests\Exporter;

use SMW\DataItemFactory;
use SMW\Exporter\ElementFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Exporter\ElementFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ElementFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testNewFromDataItemForSupportedTypes( $dataItem ) {

		$instance = new ElementFactory();

		$this->assertInstanceOf(
			'\SMW\Exporter\Element',
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

		$instance->registerCallableMapper( \SMWDataItem::TYPE_BLOB, function( $datatem ) {
			return new \stdclass;
		} );

		$this->setExpectedException( 'RuntimeException' );
		$instance->newFromDataItem( $dataItemFactory->newDIBlob( 'foo' ) );
	}

	public function supportedDataItemProvider() {

		$dataItemFactory = new DataItemFactory();

		#0
		$provider[] = [
			$dataItemFactory->newDINumber( 42 )
		];

		#1
		$provider[] = [
			$dataItemFactory->newDIBlob( 'Test' )
		];

		#2
		$provider[] = [
			$dataItemFactory->newDIBoolean( true )
		];

		#3
		$provider[] = [
			$dataItemFactory->newDIUri( 'http', '//example.org', '', '' )
		];

		#4
		$provider[] = [
			$dataItemFactory->newDITime( 1, '1970' )
		];

		#5
		$provider[] = [
			$dataItemFactory->newDIContainer( new \SMWContainerSemanticData( $dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) )
		];

		#6
		$provider[] = [
			$dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN )
		];

		#7
		$provider[] = [
			$dataItemFactory->newDIProperty( 'Foo' )
		];

		#8
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
			->will( $this->returnValue( 'Foo' ) );

		#0
		$provider[] = [
			$dataItem
		];

		#1
		$provider[] = [
			new \SMWDIGeoCoord( [ 'lat' => 52, 'lon' => 1 ] )
		];

		return $provider;
	}

}
