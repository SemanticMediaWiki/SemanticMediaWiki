<?php

namespace SMW\Tests\MediaWiki\Content;

use MediaWiki\Content\ValidationParams;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageIdentityValue;
use SMW\MediaWiki\Content\SchemaContent;
use SMW\MediaWiki\Content\SchemaContentHandler;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContentHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentHandlerTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceof(
			'\JsonContentHandler',
			new SchemaContentHandler()
		);
	}

	public function testPrepareSave() {
		$schema = $this->createMock( '\SMW\Schema\SchemaDefinition' );

		$schemaValidator = $this->createMock( '\SMW\Schema\SchemaValidator' );

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

		$title = $this->createMock( '\Title' );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->createMock( '\WikiPage' );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->createMock( '\User' );

		$parserOptions = $this->createMock( '\ParserOptions' );

		$schemaContent = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$schemaContent->setServices( $schemaFactory );

		$instance = new SchemaContentHandler();
		$page = new PageIdentityValue( 0, 1, 'Foo', PageIdentity::LOCAL );
		$validationParams = new ValidationParams( $page, 0 );

		$this->assertInstanceof(
			'\Status',
			$instance->validateSave( $schemaContent, $validationParams )
		);
	}

	public function testPrepareSave_InvalidJSON() {
		$schema = $this->createMock( '\SMW\Schema\SchemaDefinition' );

		$schemaValidator = $this->createMock( '\SMW\Schema\SchemaValidator' );

		$schemaValidator->expects( $this->once() )
			->method( 'validate' )
			->will( $this->returnValue( [] ) );

		$schemaFactory = $this->createMock( '\SMW\Schema\SchemaFactory' );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->will( $this->returnValue( $schema ) );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->will( $this->returnValue( $schemaValidator ) );

		$title = $this->createMock( '\Title' );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->createMock( '\WikiPage' );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->createMock( '\User' );

		$parserOptions = $this->createMock( '\ParserOptions' );

		$schemaContent = new SchemaContent(
			'Foo'
		);

		$schemaContent->setServices( $schemaFactory );

		$instance = new SchemaContentHandler();
		$page = new PageIdentityValue( 0, 1, 'Foo', PageIdentity::LOCAL );
		$validationParams = new ValidationParams( $page, 0 );

		$status = $instance->validateSave( $schemaContent, $validationParams );
		$this->assertEquals( true, $status->isOK() );
	}

	public function testSerializationOfClassInstance() {
		$title = $this->createMock( '\Title' );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$page = $this->createMock( '\WikiPage' );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$user = $this->createMock( '\User' );

		$parserOptions = $this->createMock( '\ParserOptions' );

		$schemaContent = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		// Use an actual factory instance to ensure a "real" DB instance is
		// invoked and would force a "RuntimeException: Database serialization
		// may cause problems, since the connection is not restored on wakeup."
		$schemaContent->setServices( new \SMW\Schema\SchemaFactory() );

		$instance = new SchemaContentHandler();
		$page = new PageIdentityValue( 0, 1, 'Foo', PageIdentity::LOCAL );
		$validationParams = new ValidationParams( $page, 0 );

		$instance->validateSave( $schemaContent, $validationParams );

		$this->assertIsString( serialize( $instance ) );
	}
}
