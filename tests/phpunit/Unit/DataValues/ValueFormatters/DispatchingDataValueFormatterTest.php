<?php

namespace SMW\Tests\Unit\DataValues\ValueFormatters;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;

/**
 * @covers \SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DispatchingDataValueFormatterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DispatchingDataValueFormatter::class,
			new DispatchingDataValueFormatter()
		);
	}

	public function testGetDataValueFormatterForMatchableDataValue() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			DataValueFormatter::class,
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testGetDefaultDispatchingDataValueFormatterForMatchableDataValue() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			DataValueFormatter::class,
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testPrioritizeDispatchableDataValueFormatter() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$defaultDataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$defaultDataValueFormatter->expects( $this->never() )
			->method( 'isFormatterFor' );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $defaultDataValueFormatter );
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			DataValueFormatter::class,
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testTryToGetDataValueFormatterForNonDispatchableDataValueThrowsException() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( false );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->expectException( 'RuntimeException' );
		$instance->getDataValueFormatterFor( $dataValue );
	}

}
