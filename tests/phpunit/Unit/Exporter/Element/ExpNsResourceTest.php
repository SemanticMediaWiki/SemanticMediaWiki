<?php

namespace SMW\Tests\Exporter\Element;

use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpNsResource;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\Exporter\Element\ExpNsResource
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpNsResourceTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpNsResource',
			new ExpNsResource( '', '', '', null )
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWExpNsResource',
			new \SMWExpNsResource( '', '', '', null )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testAccessToMethods( $localName, $namespace, $namespaceId, $dataItem ) {

		$instance = new ExpNsResource(
			$localName,
			$namespace,
			$namespaceId,
			$dataItem
		);

		$this->assertEquals(
			$namespaceId . ':' . $localName,
			$instance->getQName()
		);

		$this->assertEquals(
			$namespace . $localName,
			$instance->getUri()
		);

		$this->assertEquals(
			$localName,
			$instance->getLocalName()
		);

		$this->assertEquals(
			$namespace,
			$instance->getNamespace()
		);

		$this->assertEquals(
			$namespaceId,
			$instance->getNamespaceId()
		);

		$this->assertEquals(
			$dataItem,
			$instance->getDataItem()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testSerializiation( $localName, $namespace, $namespaceId, $dataItem, $expected ) {

		$instance = new ExpNsResource(
			$localName,
			$namespace,
			$namespaceId,
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
	public function testInvalidConstructorThrowsException( $localName, $namespace, $namespaceId, $dataItem ) {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = new ExpNsResource(
			$localName,
			$namespace,
			$namespaceId,
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
			'', '', '', null,
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => '||',
				'dataitem' => null
			)
		);

		#1
		$provider[] = array(
			'Foo', '', '', null,
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => 'Foo||',
				'dataitem' => null
			)
		);

		#2
		$provider[] = array(
			'Foo', 'Bar', '', null,
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => 'Foo|Bar|',
				'dataitem' => null
			)
		);

		#3
		$provider[] = array(
			'Foo', 'Bar', 'Fum', null,
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => 'Foo|Bar|Fum',
				'dataitem' => null
			)
		);

		#4
		$provider[] = array(
			'Foo', 'Bar', 'Fum', new DIWikiPage( 'Foo', NS_MAIN ),
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => 'Foo|Bar|Fum',
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
			array(), '', '', null
		);

		#1
		$provider[] = array(
			'', array(), '', null
		);

		#2
		$provider[] = array(
			'', '', array(), null
		);

		return $provider;
	}

	public function serializationMissingElementProvider() {

		#0
		$provider[] = array(
			array()
		);

		#1
		$provider[] = array(
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE
			)
		);

		#2
		$provider[] = array(
			array(
				'type' => 'BogusType'
			)
		);

		#3
		$provider[] = array(
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'dataitem' => null
			)
		);

		#4
		$provider[] = array(
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => '',
				'dataitem' => null
			)
		);

		#5
		$provider[] = array(
			array(
				'type' => ExpNsResource::TYPE_NSRESOURCE,
				'uri'  => '|',
				'dataitem' => null
			)
		);

		return $provider;
	}

}
