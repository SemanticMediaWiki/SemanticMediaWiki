<?php

namespace SMW\Tests\Exporter\Serializer;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Serializer\RDFXMLSerializer;
use SMW\Tests\PHPUnitCompat;
use SMWExpData as ExpData;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpLiteral;

/**
 * @covers \SMW\Exporter\Serializer\RDFXMLSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class RDFXMLSerializerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testFlushContent_Empty() {

		$instance = new RDFXMLSerializer();
		$instance->startSerialization();
		$instance->finishSerialization();

		$this->assertContains(
			'<?xml version="1.0" encoding="UTF-8"?>',
			$instance->flushContent()
		);
	}

	public function testFlushContent_EmptyElement() {

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new RDFXMLSerializer();

		$instance->startSerialization();
		$instance->serializeExpData( $expData );
		$instance->finishSerialization();

		$this->assertContains(
			'<rdf:Resource rdf:about="BarFoobar" />',
			$instance->flushContent()
		);
	}

	public function testFlushContent_SingleElement() {

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'ns:Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'ns:Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$instance = new RDFXMLSerializer();

		$instance->startSerialization();
		$instance->serializeExpData( $expData );
		$instance->finishSerialization();

		$this->assertContains(
			"	<rdf:Resource rdf:about=\"BarFoobar\">\n" .
			"		<ns:Lu:Li rdf:datatype=\"Bar\">Foo</ns:Lu:Li>\n" .
			"	</rdf:Resource>\n" .
			"	<owl:DatatypeProperty rdf:about=\"LaLi\" />\n",
			$instance->flushContent()
		);
	}

}
