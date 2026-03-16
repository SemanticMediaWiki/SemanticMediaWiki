<?php

namespace SMW\Tests\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\Exporter\ExpDataFactory;
use SMW\Exporter\ExporterFactory;
use SMW\Exporter\Serializer\RDFXMLSerializer;
use SMW\Exporter\Serializer\Serializer;
use SMW\Exporter\Serializer\TurtleSerializer;

/**
 * @covers \SMW\Exporter\ExporterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExporterFactoryTest extends TestCase {

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
		$serializer = $this->getMockBuilder( Serializer::class )
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
			Serializer::class,
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
			RDFXMLSerializer::class,
			$instance->newRDFXMLSerializer()
		);
	}

	public function testCanConstructTurtleSerializer() {
		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			TurtleSerializer::class,
			$instance->newTurtleSerializer()
		);
	}

	public function testCanConstructExpDataFactory() {
		$exporter = $this->getMockBuilder( '\SMWExporter' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ExporterFactory();

		$this->assertInstanceOf(
			ExpDataFactory::class,
			$instance->newExpDataFactory( $exporter )
		);
	}

	public function testConfirmAllCanConstructMethodsWereCalled() {
		// Available class methods to be tested
		$classMethods = get_class_methods( ExporterFactory::class );

		// Match all "testCanConstruct" to define the expected set of methods
		$testMethods = preg_grep( '/^testCanConstruct/', get_class_methods( $this ) );

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
