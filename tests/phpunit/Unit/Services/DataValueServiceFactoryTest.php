<?php

namespace SMW\Tests\Services;

use SMW\Services\DataValueServiceFactory;

/**
 * @covers \SMW\Services\DataValueServiceFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServiceFactoryTest extends \PHPUnit_Framework_TestCase {

	private $containerBuilder;

	protected function setUp() {
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

		$this->assertInternalType(
			'string',
			DataValueServiceFactory::SERVICE_FILE
		);
	}

	public function testNewDataValueByType() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->containerBuilder->expects( $this->once() )
			->method( 'isRegistered' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_INSTANCE . 'foo' ) )
			->will( $this->returnValue( true ) );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->newDataValueByType( 'foo', 'bar' );
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
			->will( $this->returnValue( true ) );

		$this->containerBuilder->expects( $this->once() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->will( $this->returnValue( $dataValueFormatter ) );

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
			->will( $this->returnValue( false ) );

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'singleton' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_FORMATTER ) )
			->will( $this->returnValue( $dataValueFormatter ) );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->getValueFormatter( $dataValue );
	}

	public function testImportExtraneousFunctions() {

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'registerCallback' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_EXT_FUNCTION . 'Foo' ) );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->importExtraneousFunctions( array(
			'Foo' => function() { return 'Foo'; }
		) );
	}

	public function testNewExtraneousFunctionByName() {

		$this->containerBuilder->expects( $this->atLeastOnce() )
			->method( 'create' )
			->with( $this->stringContains( DataValueServiceFactory::TYPE_EXT_FUNCTION . 'Foo' ) );

		$instance = new DataValueServiceFactory(
			$this->containerBuilder
		);

		$instance->newExtraneousFunctionByName( 'Foo' );
	}

}
