<?php

namespace SMW\Tests\Query\PrintRequest;

use SMW\DataValues\PropertyChainValue;
use SMW\Localizer;
use SMW\Query\PrintRequest;
use SMW\Query\PrintRequest\Deserializer;
use SMWPropertyValue as PropertyValue;

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
		 // Category
		$provider[] = array(
			'Categories',
			false,
			$categoryName,
			PrintRequest::PRINT_CATS,
			null,
			''
		);

		#5
		$provider[] = array(
			'Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'foobar'
		);

		#6
		$provider[] = array(
			'Foo#',
			false,
			'Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'-'
		);

		#7, 1464
		$provider[] = array(
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>'
		);

		#8
		$provider[] = array(
			'Foo.Bar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			''
		);

		#9
		$provider[] = array(
			'Foo.Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			'foobar'
		);

		#10 ...
		$provider[] = array(
			'Foo = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		);

		#11 ...
		$provider[] = array(
			'Foo#Bar = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'Bar'
		);

		#12 #481
		$provider[] = array(
			'#=Foo',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			''
		);

		#13 #481
		$provider[] = array(
			'#=Foo#',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			'-'
		);

		#13 #481
		$provider[] = array(
			'#=Foo#-',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			'-'
		);

		return $provider;
	}

}
