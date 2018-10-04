<?php

namespace SMW\Tests\Exporter\Element;

use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpResource;
use SMWDataItem as DataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Exporter\Element\ExpResource
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpResourceTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpResource',
			new ExpResource( '', null )
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWExpResource',
			new \SMWExpResource( '', null )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testAccessToMethods( $uri, $dataItem, $isBlankNode ) {

		$instance = new ExpResource(
			$uri,
			$dataItem
		);

		$this->assertEquals(
			$isBlankNode,
			$instance->isBlankNode()
		);

		$this->assertEquals(
			$uri,
			$instance->getUri()
		);

		$this->assertEquals(
			$dataItem,
			$instance->getDataItem()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testSerializiation( $uri, $dataItem, $isBlankNode, $expected ) {

		$instance = new ExpResource(
			$uri,
			$dataItem
		);

		$this->assertEquals(
			$expected,
			$instance->getSerialization()
		);

		$this->assertEquals(
			$instance,
			ExpElement::newFromSerialization( $instance->getSerialization() )
		);
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 */
	public function testInvalidConstructorThrowsException( $uri, $dataItem ) {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = new ExpResource(
			$uri,
			$dataItem
		);
	}

	/**
	 * @dataProvider serializationMissingElementProvider
	 */
	public function testDeserializiationForMissingElementThrowsException( $serialization ) {

		$this->setExpectedException( 'RuntimeException' );

		ExpElement::newFromSerialization(
			$serialization
		);
	}

	public function constructorProvider() {

		#0
		$provider[] = [
			'', null,
			true,
			[
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => '',
				'dataitem' => null
			]
		];

		#1
		$provider[] = [
			'Foo', null,
			false,
			[
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => 'Foo',
				'dataitem' => null
			]
		];

		#4
		$provider[] = [
			'Foo', new DIWikiPage( 'Foo', NS_MAIN ),
			false,
			[
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => 'Foo',
				'dataitem' => [
					'type' => DataItem::TYPE_WIKIPAGE,
					'item' => 'Foo#0##'
				]
			]
		];

		return $provider;
	}

	public function invalidConstructorProvider() {

		#0
		$provider[] = [
			[], null
		];

		return $provider;
	}

	public function serializationMissingElementProvider() {

		#0
		$provider[] = [
			[]
		];

		#1 Missing dataitem
		$provider[] = [
			[
				'type' => ExpResource::TYPE_RESOURCE
			]
		];

		#2 Bogus type
		$provider[] = [
			[
				'type' => 'BogusType'
			]
		];

		#3 Missing uri
		$provider[] = [
			[
				'type' => ExpResource::TYPE_RESOURCE,
				'dataitem' => null
			]
		];

		return $provider;
	}

}
