<?php

namespace SMW\Tests\Services;

use SMW\Services\DataValueServiceFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Services\DataValueServiceFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServiceFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $containerBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->containerBuilder = $this->getMockBuilder( '\Onoi\CallbackContainer\ContainerBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DataValueServiceFactory::class,
			new DataValueServiceFactory( $this->containerBuilder )
		);
	}

	public function testGetServiceFile() {
		$this->assertIsString(

			DataValueServiceFactory::SERVICE_FILE
		);
	}

	public function testNewDataValueByTypeOrClass() {
		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->once() )
			->method( 'isRegistered' )
			->with( $this->stringContains( 'bar' ) )
			->willReturn( true );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->newDataValueByTypeOrClass( 'foo', 'bar' );
	}

	public function testGetDataValueFactory() {
		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$this->assertInstanceOf(
			'\SMW\DataValueFactory',
			$instance->getDataValueFactory()
		);
	}

	public function testGetValueParser() {
		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->once() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_PARSER ) );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getValueParser( $dataValue );
	}

	public function testGetValueFormatterOnRegisteredFormatters() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->once() )
			->method( 'isRegistered' )
			->willReturn( true );

		$this->containerBuilder->expects( $this->once() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->willReturn( $dataValueFormatter );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getValueFormatter( $dataValue );
	}

	public function testGetValueFormatterOnNonRegisteredFormatters() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->once() )
			->method( 'isRegistered' )
			->willReturn( false );

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->willReturn( $dataValueFormatter );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getValueFormatter( $dataValue );
	}

	public function testGetPropertyRestrictionExaminer() {
		$propertyRestrictionExaminer = $this->getMockBuilder( '\SMW\PropertyRestrictionExaminer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( 'PropertyRestrictionExaminer' ) )
			->willReturn( $propertyRestrictionExaminer );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getPropertyRestrictionExaminer();
	}

	public function testGetDescriptionBuilderRegistry() {
		$descriptionBuilderRegistry = $this->getMockBuilder( '\SMW\Query\DescriptionBuilderRegistry' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( 'DescriptionBuilderRegistry' ) )
			->willReturn( $descriptionBuilderRegistry );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getDescriptionBuilderRegistry();
	}

	public function testGetUnitConverter() {
		$unitConverter = $this->getMockBuilder( '\SMW\DataValues\Number\UnitConverter' )
			->disableOriginalConstructor()
			->getMock();

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( 'UnitConverter' ) )
			->willReturn( $unitConverter );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getUnitConverter();
	}

}
