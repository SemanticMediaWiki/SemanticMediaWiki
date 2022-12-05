<?php

namespace SMW\Tests\Exporter\Serializer;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Tests\PHPUnitCompat;
use SMWExpData as ExpData;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpLiteral;

/**
 * @covers \SMW\Exporter\Serializer\TurtleSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class TurtleSerializerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testFlushContent_Empty() {

		$instance = new TurtleSerializer();
		$instance->startSerialization();
		$instance->finishSerialization();

		$this->assertContains(
			'@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>',
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

		$instance = new TurtleSerializer();

		$instance->startSerialization();
		$instance->serializeExpData( $expData );
		$instance->finishSerialization();

		$this->assertContains(
			"ns:Mo:Foobar\n" .
			" 	ns:Lu:Li  \"Foo\"^^<Bar> .\n",
			$instance->flushContent()
		);
	}

}
