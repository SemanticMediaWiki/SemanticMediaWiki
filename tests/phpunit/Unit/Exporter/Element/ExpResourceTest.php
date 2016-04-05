<?php

namespace SMW\Tests\Exporter\Element;

use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpResource;
use SMWDataItem as DataItem;

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
		$provider[] = array(
			'', null,
			true,
			array(
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => '',
				'dataitem' => null
			)
		);

		#1
		$provider[] = array(
			'Foo', null,
			false,
			array(
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => 'Foo',
				'dataitem' => null
			)
		);

		#4
		$provider[] = array(
			'Foo', new DIWikiPage( 'Foo', NS_MAIN ),
			false,
			array(
				'type' => ExpResource::TYPE_RESOURCE,
				'uri'  => 'Foo',
				'dataitem' => array(
					'type' => DataItem::TYPE_WIKIPAGE,
					'item' => 'Foo#0#'
				)
			)
		);

		return $provider;
	}

	public function invalidConstructorProvider() {

		#0
		$provider[] = array(
			array(), null
		);

		return $provider;
	}

	public function serializationMissingElementProvider() {

		#0
		$provider[] = array(
			array()
		);

		#1 Missing dataitem
		$provider[] = array(
			array(
				'type' => ExpResource::TYPE_RESOURCE
			)
		);

		#2 Bogus type
		$provider[] = array(
			array(
				'type' => 'BogusType'
			)
		);

		#3 Missing uri
		$provider[] = array(
			array(
				'type' => ExpResource::TYPE_RESOURCE,
				'dataitem' => null
			)
		);

		return $provider;
	}

}
