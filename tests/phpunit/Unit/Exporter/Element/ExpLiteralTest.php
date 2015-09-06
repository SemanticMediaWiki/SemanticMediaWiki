<?php

namespace SMW\Tests\Exporter\Element;

use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpElement;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\Exporter\Element\ExpLiteral
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpLiteralTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpLiteral',
			new ExpLiteral( '', '', '', null )
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWExpLiteral',
			new \SMWExpLiteral( '', '', '', null )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testAccessToMethods( $lexicalForm, $datatype, $lang, $dataItem ) {

		$instance = new ExpLiteral(
			$lexicalForm,
			$datatype,
			$lang,
			$dataItem
		);

		$this->assertEquals(
			$datatype,
			$instance->getDatatype()
		);

		$this->assertEquals(
			$lang,
			$instance->getLang()
		);

		$this->assertEquals(
			$lexicalForm,
			$instance->getLexicalForm()
		);

		$this->assertEquals(
			$dataItem,
			$instance->getDataItem()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testSerializiation( $lexicalForm, $datatype, $lang, $dataItem, $expected ) {

		$instance = new ExpLiteral(
			$lexicalForm,
			$datatype,
			$lang,
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
	public function testInvalidConstructorThrowsException( $lexicalForm, $datatype, $lang, $dataItem ) {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = new ExpLiteral(
			$lexicalForm,
			$datatype,
			$lang,
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
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'  => '',
				'datatype'  => '',
				'lang'  => '',
				'dataitem' => null
			)
		);

		#1
		$provider[] = array(
			'Foo', '', '', null,
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'  => 'Foo',
				'datatype' => '',
				'lang'     => '',
				'dataitem' => null
			)
		);

		#2
		$provider[] = array(
			'Foo', 'bar', '', null,
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'  => 'Foo',
				'datatype' => 'bar',
				'lang'     => '',
				'dataitem' => null
			)
		);

		#3
		$provider[] = array(
			'Foo', 'bar', '', new DIWikiPage( 'Foo', NS_MAIN ),
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'   => 'Foo',
				'datatype'  => 'bar',
				'lang'      => '',
				'dataitem' => array(
					'type' => DataItem::TYPE_WIKIPAGE,
					'item' => 'Foo#0#'
				)
			)
		);

		#4
		$provider[] = array(
			'Foo', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#langString', 'en', new DIWikiPage( 'Foo', NS_MAIN ),
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'   => 'Foo',
				'datatype'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#langString',
				'lang'      => 'en',
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

		#1
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

		#1 Missing dataitem
		$provider[] = array(
			array(
				'type' => ExpLiteral::TYPE_LITERAL
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
				'type' => ExpLiteral::TYPE_LITERAL,
				'dataitem' => null
			)
		);

		#4 Missing lexical
		$provider[] = array(
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'datatype' => 'foo',
				'dataitem' => null
			)
		);

		#4 Missing datatype
		$provider[] = array(
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'  => 'foo',
				'dataitem' => null
			)
		);

		#5 Missing lang
		$provider[] = array(
			array(
				'type' => ExpLiteral::TYPE_LITERAL,
				'lexical'  => 'foo',
				'datatype' => 'foo',
				'dataitem' => null
			)
		);

		return $provider;
	}

}
