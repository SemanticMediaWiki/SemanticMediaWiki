<?php

namespace SMW\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Services\DataValueServiceFactory;
use SMW\Services\ServicesContainer;
use SMW\Store;

/**
 * @covers \SMW\Services\DataValueServiceFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServiceFactoryTest extends TestCase {

	private $servicesContainer;
	private Store $store;

	protected function setUp(): void {
		parent::setUp();

		$this->servicesContainer = $this->getMockBuilder( ServicesContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$this->store = $this->createMock( Store::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DataValueServiceFactory::class,
			new DataValueServiceFactory( $this->servicesContainer, $this->store )
		);
	}

	public function testGetServiceFile() {
		$this->assertIsString(

			DataValueServiceFactory::SERVICE_FILE
		);
	}

	public function testNewDataValueByTypeOrClass() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->servicesContainer->expects( $this->once() )
			->method( 'isRegistered' )
			->with( $this->stringContains( 'bar' ) )
			->willReturn( true );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->newDataValueByTypeOrClass( 'foo', 'bar' );
	}

	public function testGetDataValueFactory() {
		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$this->assertInstanceOf(
			DataValueFactory::class,
			$instance->getDataValueFactory()
		);
	}

	public function testGetValueParser() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->servicesContainer->expects( $this->once() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_PARSER ) );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->getValueParser( $dataValue );
	}

	public function testGetValueFormatterOnRegisteredFormatters() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->servicesContainer->expects( $this->once() )
			->method( 'isRegistered' )
			->willReturn( true );

		$this->servicesContainer->expects( $this->once() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->willReturn( $dataValueFormatter );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->getValueFormatter( $dataValue );
	}

	public function testGetValueFormatterOnNonRegisteredFormatters() {
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->servicesContainer->expects( $this->once() )
			->method( 'isRegistered' )
			->willReturn( false );

		$this->servicesContainer->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->willReturn( $dataValueFormatter );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->getValueFormatter( $dataValue );
	}

	public function testGetDescriptionBuilderRegistry() {
		$descriptionBuilderRegistry = $this->getMockBuilder( DescriptionBuilderRegistry::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->servicesContainer->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( 'DescriptionBuilderRegistry' ) )
			->willReturn( $descriptionBuilderRegistry );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->getDescriptionBuilderRegistry();
	}

	public function testGetUnitConverter() {
		$unitConverter = $this->getMockBuilder( UnitConverter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->servicesContainer->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( 'UnitConverter' ) )
			->willReturn( $unitConverter );

		$instance = new DataValueServiceFactory(
			$this->servicesContainer,
			$this->store
		);

		$instance->getUnitConverter();
	}

}
