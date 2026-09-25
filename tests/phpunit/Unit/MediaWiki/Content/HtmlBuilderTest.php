<?php

namespace SMW\Tests\Unit\MediaWiki\Content;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Content\HtmlBuilder;

/**
 * @covers \SMW\MediaWiki\Content\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlBuilderTest extends TestCase {

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

		$this->assertIsString(

			$instance->build( $key, $params )
		);
	}

	public function testScriptBearingSchemaDescriptionIsEscaped() {
		$instance = new HtmlBuilder();

		$html = $instance->build( 'schema_summary', $this->schemaSummaryParams( '<img src=x onerror=alert(1)>' ) );

		$this->assertStringNotContainsString( '<img src=x onerror=', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=', $html );
	}

	public function testBenignSchemaDescriptionRoundTrips() {
		$instance = new HtmlBuilder();

		$html = $instance->build( 'schema_summary', $this->schemaSummaryParams( 'Tom & Jerry description' ) );

		$this->assertStringContainsString( 'Tom &amp; Jerry description', $html );
		$this->assertStringNotContainsString( 'Tom &amp;amp; Jerry description', $html );
	}

	private function schemaSummaryParams( string $schemaDescription ): array {
		return [
			'attributes' => [
				'schema_description' => $schemaDescription,
				'type' => '',
				'type_description' => '',
				'tag' => ''
			],
			'attributes_extra' => [
				'href_description' => '/index.php/Property:Schema_description',
				'msg_description' => 'Schema description'
			],
			'error_params' => []
		];
	}

	public function buildParamsProvider() {
		yield [
			'schema_head',
			[
				'link' => 'Foo',
				'description' => 'bar',
				'schema-title' => '...',
				'summary-title' => '...',
				'schema_summary' => '...',
				'error' => 'err---rr',
				'error-title' => 'error',
				'usage_count' => 42,
				'usage-title' => 'usage',
				'usage' => '...',
				'schema_body' => '...'
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
