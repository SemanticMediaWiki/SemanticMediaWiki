<?php

namespace SMW\Tests\MediaWiki\Content;

use SMW\MediaWiki\Content\HtmlBuilder;
use SMW\Schema\Schema;

/**
 * @covers \SMW\MediaWiki\Content\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			HtmlBuilder::class,
			new HtmlBuilder()
		);
	}

	/**
	 * @dataProvider buildParamsProvider
	 */
	public function testBuild( $key, $params ) {

		$instance = new HtmlBuilder();

		$this->assertInternalType(
			'string',
			$instance->build( $key, $params )
		);
	}

	public function buildParamsProvider() {

		yield [
			'schema_head',
			[
				'link' => 'Foo',
				'description' => 'bar',
				'schema-title' => '...',
				'error' => 'err---rr',
				'error-title' => 'error'
			]
		];

		yield [
			'schema_body',
			[
				'text' => 'Foo',
				'unknown_type' => 'bar',
				'isYaml' => false
			]
		];

		yield [
			'schema_error_text',
			[
				'list' => [],
				'schema' => 'Foo'
			]
		];

		yield [
			'schema_error',
			[
				'text' => '...',
				'msg' => 'Foo'
			]
		];

		yield [
			'schema_footer',
			[
				'href_type' => '...',
				'link_type' => 'Foo',
				'msg_type'  => 'Bar',
				'tags'      => [],
				'href_tag'  => 'Foobar'
			]
		];

		yield [
			'schema_unknown_type',
			[
				'msg' => 'Foo'
			]
		];

		yield [
			'schema_help_link',
			[
				'href' => 'Foo'
			]
		];

	}

}
