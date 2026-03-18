<?php

namespace SMW\Tests\Export;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\Concept;
use SMW\DataItems\DataItem;
use SMW\DataItems\Number;
use SMW\DataItems\WikiPage;
use SMW\Export\ExpData;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;

/**
 * @covers \SMW\Export\Exporter
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ExporterTest extends TestCase {

	// @see #795
	public function testExportDataForPropertyPage() {
		$propertyPage = new WikiPage( 'Foo', SMW_NS_PROPERTY );

		$expData = Exporter::getInstance()->makeExportDataForSubject( $propertyPage );

		$this->assertInstanceOf(
			ExpData::class,
			$expData
		);

		$this->assertInstanceOf(
			ExpNsResource::class,
			$expData->getSubject()
		);
	}

	/**
	 * @dataProvider dataItemExpElementProvider
	 */
	public function testnewExpElement( DataItem $dataItem, $instance ) {
		if ( $instance === null ) {
			return $this->assertNull( Exporter::getInstance()->newExpElement( $dataItem ) );
		}

		$this->assertInstanceOf(
			$instance,
			Exporter::getInstance()->newExpElement( $dataItem )
		);
	}

	/**
	 * @dataProvider uriDataItemProvider
	 * #378
	 */
	public function testFindDataItemForExpElement( $uri, $expectedDataItem ) {
		$uri = Exporter::getInstance()->getNamespaceUri( 'wiki' ) . $uri;

		$this->assertEquals(
			$expectedDataItem,
			Exporter::getInstance()->findDataItemForExpElement( new ExpResource( $uri ) )
		);
	}

	public function dataItemExpElementProvider() {
		// #0 (bug 56643)
		$provider[] = [ new Number( 9001 ), ExpElement::class ];

		$provider[] = [ new Blob( 'foo' ), ExpElement::class ];
		$provider[] = [ new Boolean( true ), ExpElement::class ];

		$provider[] = [ new Concept( 'Foo', '', '', '', '' ), 'SMWExpData' ];

		return $provider;
	}

	public function uriDataItemProvider() {
		$provider[] = [ 'Foo', new WikiPage( 'Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Foo#Bar', new WikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Foo#Bar#Oooo', new WikiPage( 'Foo', NS_MAIN, '', 'Bar#Oooo' ) ];
		$provider[] = [ 'Property:Foo', new WikiPage( 'Foo', SMW_NS_PROPERTY, '', '' ) ];
		$provider[] = [ 'Concept:Foo', new WikiPage( 'Foo', SMW_NS_CONCEPT, '', '' ) ];
		$provider[] = [ 'Unknown:Foo', new WikiPage( 'Unknown:Foo', NS_MAIN, '', '' ) ];
		$provider[] = [ 'Unknown:Foo#Bar', new WikiPage( 'Unknown:Foo', NS_MAIN, '', 'Bar' ) ];
		$provider[] = [ 'Property:Foo#Bar', new WikiPage( 'Foo', SMW_NS_PROPERTY, '', 'Bar' ) ];

		return $provider;
	}

}
