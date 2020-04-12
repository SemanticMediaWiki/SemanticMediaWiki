<?php

namespace SMW\Tests\Exporter;

use SMW\DataItemFactory;
use SMW\Exporter\ExporterFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Exporter\ExporterFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ExporterFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExporterFactory::class,
			new ExporterFactory()
		);
	}

	public function testGetExporter() {

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMWExporter',
			$instance->getExporter()
		);
	}

	public function testCanConstructExportController() {

		$serializer = $this->getMockBuilder( '\SMW\Exporter\Serializer\Serializer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMWExportController',
			$instance->newExportController( $serializer )
		);
	}

	/**
	 * @dataProvider serializerByTypeProvider
	 */
	public function testCanConstructSerializerByType( $type ) {

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMW\Exporter\Serializer\Serializer',
			$instance->newSerializerByType( $type )
		);
	}

	public function testSerializerByInvalidType_ThrowsException() {

		$instance = new ExporterFactory();

		$this->expectException( '\InvalidArgumentException' );
		$instance->newSerializerByType( 'foo' );
	}

	public function testCanConstructRDFXMLSerializer() {

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMW\Exporter\Serializer\RDFXMLSerializer',
			$instance->newRDFXMLSerializer()
		);
	}

	public function testCanConstructTurtleSerializer() {

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMW\Exporter\Serializer\TurtleSerializer',
			$instance->newTurtleSerializer()
		);
	}

	public function testCanConstructExpDataFactory() {

		$exporter = $this->getMockBuilder( '\SMWExporter' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			'\SMW\Exporter\ExpDataFactory',
			$instance->newExpDataFactory( $exporter )
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {

		// Available class methods to be tested
		$classMethods = get_class_methods( ExporterFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep('/^testCanConstruct/', get_class_methods( $this ) );

		$testMethods = array_flip(
			str_replace( 'testCanConstruct', 'new', $testMethods )
		);

		foreach ( $classMethods as $name ) {

			if ( substr( $name, 0, 3 ) !== 'new' ) {
				continue;
			}

			$this->assertArrayHasKey( $name, $testMethods );
		}
	}

	public function serializerByTypeProvider() {

		yield [
			'turtle'
		];

		yield [
			'application/x-turtle'
		];

		yield [
			'rdfxml'
		];

		yield [
			'application/rdf+xml'
		];
	}

}
