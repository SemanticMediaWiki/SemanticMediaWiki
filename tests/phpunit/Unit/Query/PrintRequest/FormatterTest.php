<?php

namespace SMW\Tests\Query\PrintRequest;

use SMW\Query\PrintRequest\Formatter;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Query\PrintRequest;

/**
 * @covers SMW\Query\PrintRequest\Formatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider printRequestProvider
	 */
	public function testFormat( $printRequest, $linker, $outputType, $expected ) {

		$this->assertSame(
			$expected,
			Formatter::format( $printRequest, $linker, $outputType )
		);
	}

	public function printRequestProvider() {

		$provider['print-cats-wiki'] = array(
			new PrintRequest( PrintRequest::PRINT_CATS, 'Foo' ),
			null,
			Formatter::FORMAT_WIKI,
			'Foo'
		);

		$provider['print-cats-html'] = array(
			new PrintRequest( PrintRequest::PRINT_CATS, 'Foo' ),
			null,
			Formatter::FORMAT_HTML,
			'Foo'
		);

		$provider['print-ccat-html'] = array(
			new PrintRequest( PrintRequest::PRINT_CCAT, 'Foo', DIWikiPage::newFromText( 'Bar' )->getTitle() ),
			null,
			Formatter::FORMAT_HTML,
			'Foo'
		);

		$provider['print-ccat-wiki'] = array(
			new PrintRequest( PrintRequest::PRINT_CCAT, 'Foo', DIWikiPage::newFromText( 'Bar' )->getTitle() ),
			null,
			Formatter::FORMAT_WIKI,
			'Foo'
		);

		$provider['print-this-wiki'] = array(
			new PrintRequest( PrintRequest::PRINT_THIS, 'Foo' ),
			null,
			Formatter::FORMAT_WIKI,
			'Foo'
		);

		$provider['print-this-html'] = array(
			new PrintRequest( PrintRequest::PRINT_THIS, 'Foo' ),
			null,
			Formatter::FORMAT_HTML,
			'Foo'
		);

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-wiki-no-linker'] = array(
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data ),
			null,
			Formatter::FORMAT_WIKI,
			'Foo'
		);

		$data = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Bar' );

		$provider['print-prop-html-no-linker'] = array(
			new PrintRequest( PrintRequest::PRINT_PROP, 'Foo', $data ),
			null,
			Formatter::FORMAT_HTML,
			'Foo'
		);

		return $provider;
	}

}
