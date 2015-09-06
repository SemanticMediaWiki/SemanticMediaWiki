<?php

namespace SMW\Tests\Exporter;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Exporter\XsdValueMapper;

/**
 * @covers \SMW\Exporter\XsdValueMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class XsdValueMapperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testMatchSupportedTypes( $dataItem, $xsdValue, $xsdType ) {

		$instance = new XsdValueMapper();

		$instance->process( $dataItem );

		$this->assertEquals(
			$xsdValue,
			$instance->getXsdValue()
		);

		$this->assertContains(
			$xsdType,
			$instance->getXsdType()
		);
	}

	/**
	 * @dataProvider unsupportedDataItemProvider
	 */
	public function testTryToMatchUnsupportedTypeThrowsException( $dataItem ) {

		$instance = new XsdValueMapper();

		$this->setExpectedException( 'RuntimeException' );
		$instance->process( $dataItem );
	}

	public function supportedDataItemProvider() {

		#0
		$provider[] = array(
			new \SMWDINumber( 42 ),
			'42',
			'double'
		);

		#1
		$provider[] = array(
			new \SMWDIBlob( 'Test' ),
			'Test',
			'string'
		);

		#2
		$provider[] = array(
			new \SMWDIBoolean( true ),
			'true',
			'boolean'
		);

		#3
		$provider[] = array(
			new \SMWDITime( 1, '1970' ),
			'1970Z',
			'gYear'
		);

		#4
		$provider[] = array(
			new \SMWDITime( 1, '1970', '12' ),
			'1970-12Z',
			'gYearMonth'
		);

		#5
		$provider[] = array(
			new \SMWDITime( 1, '1970', '12', '31' ),
			'1970-12-31Z',
			'date'
		);

		#6
		$provider[] = array(
			new \SMWDITime( 1, '1970', '12', '31', '12' ),
			'1970-12-31T12:00:00Z',
			'dateTime'
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

		#2
		$provider[] = array(
			new \SMWDIConcept( 'Foo', '', '', '', '' )
		);

		#3
		$provider[] = array(
			new \SMWDIUri( 'http', '//example.org', '', '' )
		);

		#4
		$provider[] = array(
			new \SMWDIContainer( new \SMWContainerSemanticData( new DIWikiPage( 'Foo', NS_MAIN ) ) )
		);

		#5
		$provider[] = array(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		#6
		$provider[] = array(
			new DIProperty( 'Foo' )
		);

		#7 Not a gregorian calendar model
		$provider[] = array(
			new \SMWDITime( 2, '1970' )
		);

		return $provider;
	}

}
