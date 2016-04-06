<?php

namespace SMW\Tests\Exporter;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\DataItemToElementEncoder;

/**
 * @covers \SMW\Exporter\DataItemToElementEncoder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class DataItemToElementEncoderTest extends \PHPUnit_Framework_TestCase {

	public function testNotSupportedEncoderResultThrowsException() {

		$instance = new DataItemToElementEncoder();

		$instance->registerDataItemEncoder( \SMWDataItem::TYPE_BLOB, function( $datatem ) {
			return new \Stdclass;
		} );

		$this->setExpectedException( 'RuntimeException' );
		$instance->mapDataItemToElement( new \SMWDIBlob( 'foo' ) );
	}

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testMapDataItemToElementForSupportedTypes( $dataItem ) {

		$instance = new DataItemToElementEncoder();

		$this->assertInstanceOf(
			'\SMW\Exporter\Element',
			$instance->mapDataItemToElement( $dataItem )
		);
	}

	/**
	 * @dataProvider unsupportedDataItemProvider
	 */
	public function testTryMappingDataItemToElementForUnsupportedTypes( $dataItem ) {

		$instance = new DataItemToElementEncoder();

		$this->assertNull(
			$instance->mapDataItemToElement( $dataItem )
		);
	}

	public function supportedDataItemProvider() {

		#0
		$provider[] = array(
			new \SMWDINumber( 42 )
		);

		#1
		$provider[] = array(
			new \SMWDIBlob( 'Test' )
		);

		#2
		$provider[] = array(
			new \SMWDIBoolean( true )
		);

		#3
		$provider[] = array(
			new \SMWDIUri( 'http', '//example.org', '', '' )
		);

		#4
		$provider[] = array(
			new \SMWDITime( 1, '1970' )
		);

		#5
		$provider[] = array(
			new \SMWDIContainer( new \SMWContainerSemanticData( new DIWikiPage( 'Foo', NS_MAIN ) ) )
		);

		#6
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		#7
		$provider[] = array(
			new DIProperty( 'Foo' )
		);

		#8
		$provider[] = array(
			new \SMWDIConcept( 'Foo', '', '', '', '' )
		);

		return $provider;
	}

	public function unsupportedDataItemProvider() {

		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->setMethods( array( '__toString' ) )
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( '__toString' )
			->will( $this->returnValue( 'Foo' ) );

		#0
		$provider[] = array(
			$dataItem
		);

		#1
		$provider[] = array(
			new \SMWDIGeoCoord( array( 'lat' => 52, 'lon' => 1 ) )
		);

		return $provider;
	}

}
