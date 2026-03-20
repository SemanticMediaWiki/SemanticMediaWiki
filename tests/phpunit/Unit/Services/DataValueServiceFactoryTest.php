<?php

namespace SMW\Tests\Unit\Services;

use Onoi\CallbackContainer\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use SMW\DataValueFactory;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\Property\RestrictionExaminer;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Services\DataValueServiceFactory;

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

	private $containerBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->containerBuilder = $this->getMockBuilder( ContainerBuilder::class )
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
			DataValueFactory::class,
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
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
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
		$dataValueFormatter = $this->getMockBuilder( DataValueFormatter::class )
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
		$propertyRestrictionExaminer = $this->getMockBuilder( RestrictionExaminer::class )
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
		$descriptionBuilderRegistry = $this->getMockBuilder( DescriptionBuilderRegistry::class )
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
		$unitConverter = $this->getMockBuilder( UnitConverter::class )
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
