<?php

namespace SMW\Tests\MediaWiki\Content;

use SMW\MediaWiki\Content\SchemaContent;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContent
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			'\JsonContent',
			new SchemaContent( 'foo' )
		);
	}

	public function testToJson() {

		$text = json_encode( [ 'Foo' => 42 ] );

		$instance = new SchemaContent( $text );

		$this->assertEquals(
			$text,
			$instance->toJson()
		);
	}

	public function testIsYaml() {

		if ( !class_exists( '\Symfony\Component\Yaml\Yaml' ) ) {
			$this->markTestSkipped( 'Skipping because `Symfony\Component\Yaml\Yaml` is not available!' );
		}

		$text = json_encode( [ 'Foo' => 42 ] );

		$instance = new SchemaContent( $text );

		$this->assertFalse(
			$instance->isYaml()
		);
	}

	public function testPrepareSave() {

		$schema = $this->getMockBuilder( '\SMW\Schema\SchemaDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator = $this->getMockBuilder( '\SMW\Schema\SchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator->expects( $this->once() )
			->method( 'validate' )
			->will( $this->returnValue( [] ) );

		$schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->will( $this->returnValue( $schema ) );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->will( $this->returnValue( $schemaValidator ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->getMockBuilder( '\wikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$instance->setServices( $schemaFactory );

		$flags = '';
		$parentRevId = '';

		$this->assertInstanceof(
			'\Status',
			$instance->prepareSave( $page, $flags, $parentRevId, $user )
		);
	}

	public function testPrepareSave_InvalidJSON() {

		$schema = $this->getMockBuilder( '\SMW\Schema\SchemaDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator = $this->getMockBuilder( '\SMW\Schema\SchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator->expects( $this->once() )
			->method( 'validate' )
			->will( $this->returnValue( [] ) );

		$schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->will( $this->returnValue( $schema ) );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->will( $this->returnValue( $schemaValidator ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->getMockBuilder( '\wikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContent(
			'Foo'
		);

		$instance->setServices( $schemaFactory );

		$flags = '';
		$parentRevId = '';

		$this->assertInstanceof(
			'\Status',
			$instance->prepareSave( $page, $flags, $parentRevId, $user )
		);
	}

	public function testSerializationOfClassInstance() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->getMockBuilder( '\wikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		// Use an actual factory instance to ensure a "real" DB instance is
		// invoked and would force a "RuntimeException: Database serialization
		// may cause problems, since the connection is not restored on wakeup."
		$instance->setServices( new \SMW\Schema\SchemaFactory() );

		$flags = '';
		$parentRevId = '';

		$instance->prepareSave( $page, $flags, $parentRevId, $user );

		$this->assertInternalType(
			'string',
			serialize( $instance )
		);
	}

	public function testPreSaveTransform() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$this->assertInstanceof(
			SchemaContent::class,
			$instance->preSaveTransform( $title, $user, $parserOptions )
		);
	}

	public function testFillParserOutput() {

		$schema = $this->getMockBuilder( '\SMW\Schema\SchemaDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator = $this->getMockBuilder( '\SMW\Schema\SchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$schemaValidator->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnValue( [] ) );

		$schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->will( $this->returnValue( $schema ) );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->will( $this->returnValue( $schemaValidator ) );

		$contentFormatter = $this->getMockBuilder( '\SMW\MediaWiki\Content\SchemaContentFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setText' );

		$parserOutput->expects( $this->once() )
			->method( 'setIndicator' );

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$instance->setServices( $schemaFactory, $contentFormatter );

		$generateHtml = true;
		$revId = 42;

		$instance->fillParserOutput( $title, $revId, $parserOptions, $generateHtml, $parserOutput );
	}

	public function testFillParserOutput_MediaWikiTypeNotFoundException() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setText' );

		$parserOutput->expects( $this->never() )
			->method( 'setIndicator' );

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$generateHtml = true;
		$revId = 42;

		$instance->fillParserOutput( $title, $revId, $parserOptions, $generateHtml, $parserOutput );
	}

}
