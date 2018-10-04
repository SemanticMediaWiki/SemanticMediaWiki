<?php

namespace SMW\Tests\Query\PrintRequest;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Query\PrintRequest;
use SMW\Query\PrintRequest\Serializer;

/**
 * @covers SMW\Query\PrintRequest\Serializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textProvider
	 */
	public function testSerialize( $printRequest, $showParams, $expected ) {

		$this->assertSame(
			$expected,
			Serializer::serialize( $printRequest, $showParams )
		);
	}

	public function textProvider() {

		$category = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );

		$provider['print-cats'] = [
			new PrintRequest( PrintRequest::PRINT_CATS, 'Foo' ),
			false,
			"?{$category}=Foo"
		];

		$provider['print-ccat'] = [
			new PrintRequest( PrintRequest::PRINT_CCAT, 'Foo', DIWikiPage::newFromText( 'Bar' )->getTitle() ),
			false,
			'?Bar=Foo'
		];

		$provider['print-this'] = [
			new PrintRequest( PrintRequest::PRINT_THIS, 'Foo' ),
			false,
			'?=Foo'
		];

		$provider['print-this-plain'] = [
			new PrintRequest( PrintRequest::PRINT_THIS, 'Foo', null, '-' ),
			false,
			'?=Foo#-'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data ),
			false,
			'?Bar#=Foo'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-output'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data, 'foobar' ),
			false,
			'?Bar#foobar=Foo'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-output-parameters-no-show'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data, 'foobar', [ 'index' => 2 ] ),
			false,
			'?Bar#foobar=Foo'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-output-parameters-show'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data, 'foobar', [ 'index' => 2 ] ),
			true,
			'?Bar#foobar=Foo|+index=2'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Modification date' );

		$provider['predefined-property'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, '', $data ),
			false,
			'?Modification date#'
		];

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-output-lang-index'] = [
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data, '', [ 'lang' => 'en', 'index' => '1' ] ),
			true,
			'?Bar=Foo|+lang=en'
		];

		return $provider;
	}

}
