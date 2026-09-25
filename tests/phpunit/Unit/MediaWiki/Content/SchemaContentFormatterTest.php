<?php

namespace SMW\Tests\Unit\MediaWiki\Content;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Content\SchemaContentFormatter;
use SMW\Schema\Schema;
use SMW\SortLetter;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContentFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentFormatterTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceof(
			SchemaContentFormatter::class,
			new SchemaContentFormatter( $this->store )
		);
	}

	public function testGetHelpLink() {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$this->assertIsString(

			$instance->getHelpLink( $schema )
		);
	}

	public function testGetText() {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->willReturnCallback( [ $this, 'schema_get' ] );

		$text = '...';
		$isYaml = false;
		$errors = [];

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$this->assertIsString(

			$instance->getText( $text, $schema, $errors )
		);
	}

	public function testGetText_Errors() {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->willReturnCallback( [ $this, 'schema_get' ] );

		$text = '...';
		$isYaml = false;

		$errors = [
			[ 'property' => 'foo', 'message' => '---' ]
		];

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$this->assertIsString(

			$instance->getText( $text, $schema, $errors )
		);
	}

	public function testMarkupSchemaDescriptionDoesNotRenderAsRawTagInJsonDump() {
		$schema = $this->newSchemaReturningEmptyValues();

		$text = '{"type":"FOO","description":"<img src=x onerror=alert(1)>"}';

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$html = $instance->getText( $text, $schema, [] );

		$this->assertStringNotContainsString(
			'<img',
			$html
		);

		$this->assertStringContainsString(
			'onerror=alert(1)',
			$html
		);
	}

	public function testBenignSchemaDescriptionRoundTripsAsValidPrettyJson() {
		$schema = $this->newSchemaReturningEmptyValues();

		$text = '{"type":"FOO","description":"Just plain text"}';

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$html = $instance->getText( $text, $schema, [] );

		$this->assertStringContainsString(
			'"description": "Just plain text"',
			$html
		);
	}

	public function testMarkupSchemaDescriptionDoesNotRenderAsRawTagInYamlDump() {
		$schema = $this->newSchemaReturningEmptyValues();

		$text = "type: FOO\ndescription: \"<img src=x onerror=alert(1)>\"\n";

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$instance->isYaml( true );

		$html = $instance->getText( $text, $schema, [] );

		$this->assertStringNotContainsString(
			'<img',
			$html
		);

		$this->assertStringContainsString(
			'onerror=alert(1)',
			$html
		);
	}

	public function testBenignYamlSchemaDescriptionIsShownAsText() {
		$schema = $this->newSchemaReturningEmptyValues();

		$text = "type: FOO\ndescription: Just plain text\n";

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$instance->isYaml( true );

		$html = $instance->getText( $text, $schema, [] );

		$this->assertStringContainsString(
			'description: Just plain text',
			$html
		);
	}

	public function testMarkupSchemaTagRendersEscapedInSummaryTable() {
		$schema = $this->newSchemaReturningTags( [ '<img src=x onerror=alert(1)>' ] );

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$html = $instance->getText( '...', $schema, [] );

		$this->assertStringNotContainsString(
			'<img src=x onerror=alert(1)>',
			$html
		);

		$this->assertStringContainsString(
			'&lt;img src=x onerror=alert(1)&gt;',
			$html
		);
	}

	public function testBenignSchemaTagRendersAsPropertySearchLink() {
		$schema = $this->newSchemaReturningTags( [ 'Benign' ] );

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$html = $instance->getText( '...', $schema, [] );

		$this->assertStringContainsString(
			'SearchByProperty',
			$html
		);

		$this->assertStringContainsString(
			'>Benign</a>',
			$html
		);
	}

	private function newSchemaReturningTags( array $tags ) {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->willReturnCallback( static fn ( $key ) => $key === Schema::SCHEMA_TAG ? $tags : '' );

		return $schema;
	}

	private function newSchemaReturningEmptyValues() {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->willReturnCallback( [ $this, 'schema_get' ] );

		return $schema;
	}

	public function testGetUsage_Empty() {
		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$instance->setType( [ 'usage_lookup' => 'Foo' ] );

		$this->assertEquals(
			[ '', 0 ],
			$instance->getUsage( $schema )
		);
	}

	public function testGetUsage() {
		$sortLetter = $this->getMockBuilder( SortLetter::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ $dataItem ] );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $sortLetter );

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$instance->setType( [ 'usage_lookup' => 'Foo' ] );

		[ $usage, $count ] = $instance->getUsage( $schema );

		$this->assertStringContainsString(
			'smw-columnlist-container',
			$usage
		);
	}

	public function testGetUsage_MultipleProperties() {
		$sortLetter = $this->getMockBuilder( SortLetter::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$schema = $this->getMockBuilder( Schema::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ $dataItem ] );

		$this->store->expects( $this->any() )
			->method( 'service' )
			->willReturn( $sortLetter );

		$instance = new SchemaContentFormatter(
			$this->store
		);

		$instance->setType( [ 'usage_lookup' => [ 'Foo', 'Bar' ] ] );

		[ $usage, $count ] = $instance->getUsage( $schema );

		$this->assertStringContainsString(
			'smw-columnlist-container',
			$usage
		);
	}

	public function schema_get( $key ) {
		return $key === Schema::SCHEMA_TAG ? [] : '';
	}

}
