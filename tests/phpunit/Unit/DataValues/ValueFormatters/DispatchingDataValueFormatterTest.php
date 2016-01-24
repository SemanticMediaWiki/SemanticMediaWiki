<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;

/**
 * @covers \SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DispatchingDataValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter',
			new DispatchingDataValueFormatter()
		);
	}

	public function testGetDataValueFormatterForMatchableDataValue() {

		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testGetDefaultDispatchingDataValueFormatterForMatchableDataValue() {

		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testPrioritizeDispatchableDataValueFormatter() {

		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->will( $this->returnValue( true ) );

		$defaultDataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$defaultDataValueFormatter->expects( $this->never() )
			->method( 'isFormatterFor' );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $defaultDataValueFormatter );
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testTryToGetDataValueFormatterForNonDispatchableDataValueThrowsException() {

		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->will( $this->returnValue( false ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getDataValueFormatterFor( $dataValue );
	}

}
