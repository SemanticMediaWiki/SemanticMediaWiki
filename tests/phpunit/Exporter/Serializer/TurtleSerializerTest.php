<?php

namespace SMW\Tests\Exporter\Serializer;

use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Tests\PHPUnitCompat;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\Serializer\TurtleSerializer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class TurtleSerializerTest extends \PHPUnit\Framework\TestCase {

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
