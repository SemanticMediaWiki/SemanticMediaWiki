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

		$options = [
			'show_mode' => $showMode
		];

		$instance = Deserializer::deserialize( $text, $options );

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
		$provider[] = [
			'Foo',
			false,
			'Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		];

		#1
		$provider[] = [
			'-Foo',
			false,
			'-Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		];

		#2
		$provider[] = [
			'-Foo=Bar',
			false,
			'Bar',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		];

		#3
		// Category
		$categoryName = Localizer::getInstance()->getNamespaceTextById(
			NS_CATEGORY
		);

		$provider[] = [
			'Category',
			false,
			$categoryName,
			PrintRequest::PRINT_CATS,
			null,
			''
		];

		#4
		// Category
		$categoryName = Localizer::getInstance()->getNamespaceTextById(
			NS_CATEGORY
		);

		$provider[] = [
			'category',
			false,
			$categoryName,
			PrintRequest::PRINT_CATS,
			null,
			''
		];

		#5
		// Category
		$provider[] = [
			'Categories',
			false,
			$categoryName,
			PrintRequest::PRINT_CATS,
			null,
			''
		];

		#6
		// "... ask for one particular category ... contains X for all pages
		// that directly belong to that category ..."
		$label = Localizer::getInstance()->createTextWithNamespacePrefix(
			NS_CATEGORY,
			'Foo'
		);

		$provider[] = [
			$label,
			false,
			// Label
			'Foo',
			// Mode
			PrintRequest::PRINT_CCAT,
			//DataInstance
			null,
			// OutputFormat
			'x'
		];

		#7
		$provider[] = [
			'Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'foobar'
		];

		#8
		$provider[] = [
			'Foo#',
			false,
			'Foo',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'-'
		];

		#9, 1464
		$provider[] = [
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>'
		];

		#10
		$provider[] = [
			'Foo.Bar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			''
		];

		#11
		$provider[] = [
			'Foo.Bar#foobar',
			false,
			'Bar',
			PrintRequest::PRINT_CHAIN,
			PropertyChainValue::class,
			'foobar'
		];

		#12
		$provider[] = [
			'Foo = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			''
		];

		#13
		$provider[] = [
			'Foo#Bar = <span style="color: green; font-size: 120%;">Label</span>',
			false,
			'<span style="color: green; font-size: 120%;">Label</span>',
			PrintRequest::PRINT_PROP,
			PropertyValue::class,
			'Bar'
		];

		#14 #481
		$provider[] = [
			'#=Foo',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			''
		];

		#15 #481
		$provider[] = [
			'#=Foo#',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			'-'
		];

		#16 #481
		$provider[] = [
			'#=Foo#-',
			false,
			'Foo',
			PrintRequest::PRINT_THIS,
			null,
			'-'
		];

		return $provider;
	}

}
