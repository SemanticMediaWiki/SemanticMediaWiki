<?php

namespace SMW\Tests\Query\PrintRequest;

use SMW\Query\PrintRequest\Deserializer;
use SMWPropertyValue as PropertyValue;
use SMW\Localizer;
use SMW\DataValues\PropertyChainValue;
use SMW\Query\PrintRequest;

/**
 * @covers SMW\Query\PrintRequest\Deserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textProvider
	 */
	public function testDeserialize( $text, $showMode, $expectedLabel, $expectedMode, $expectedDataInstance, $expectedOutputFormat ) {

		$instance = Deserializer::deserialize( $text, $showMode );

		$this->assertEquals(
			$expectedLabel,
			$instance->getLabel()
		);

		$this->assertEquals(
			$expectedMode,
			$instance->getMode()
		);

		if ( $expectedDataInstance !== null ) {
			$this->assertInstanceOf(
				$expectedDataInstance,
				$instance->getData()
			);
		}

		$this->assertSame(
			$expectedOutputFormat,
			$instance->getOutputFormat()
		);
	}

	public function textProvider() {

		#0
		$provider[] = array(
			'Foo',
			false,
			'Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		);

		#1
		$provider[] = array(
			'-Foo',
			false,
			'-Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		);

		#2
		$provider[] = array(
			'-Foo=Bar',
			false,
			'Bar',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		);

		#3
		 // Category
		$categoryName = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		$provider[] = array(
			'Category',
			false,
			$categoryName,
			PrintRequest::PRINT_CATS,
			null,
			''
		);

		#4
		$provider[] = array(
			'Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'foobar'
		);

		#5
		$provider[] = array(
			'Foo#',
			false,
			'Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'-'
		);

		#6, 1464
		$provider[] = array(
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>'
		);

		#7
		$provider[] = array(
			'Foo.Bar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			''
		);

		#8
		$provider[] = array(
			'Foo.Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			'foobar'
		);

		#9 ...
		$provider[] = array(
			'Foo = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		);

		#10 ...
		$provider[] = array(
			'Foo#Bar = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'Bar'
		);

		return $provider;
	}

}
