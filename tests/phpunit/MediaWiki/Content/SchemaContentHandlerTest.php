<?php

namespace SMW\Tests\MediaWiki\Content;

use MediaWiki\Content\ValidationParams;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\PageIdentityValue;
use ParserOptions;
use SMW\MediaWiki\Content\SchemaContent;
use SMW\MediaWiki\Content\SchemaContentHandler;
use SMW\Schema\SchemaDefinition;
use SMW\Schema\SchemaFactory;
use SMW\Schema\SchemaValidator;
use Title;
use User;
use WikiPage;

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

	public function testValidateSave() {
		$schema = $this->createMock( SchemaDefinition::class );

		$schemaValidator = $this->createMock( SchemaValidator::class );

		$schemaValidator->expects( $this->once() )
			->method( 'validate' )
			->willReturn( [] );

		$schemaFactory = $this->createMock( SchemaFactory::class );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->willReturn( $schema );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->willReturn( $schemaValidator );

		$title = $this->createMock( Title::class );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$page = $this->createMock( WikiPage::class );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$user = $this->createMock( User::class );

		$parserOptions = $this->createMock( ParserOptions::class );

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

	public function testValidateSave_InvalidJSON() {
		$schema = $this->createMock( SchemaDefinition::class );

		$schemaValidator = $this->createMock( SchemaValidator::class );

		$schemaValidator->expects( $this->once() )
			->method( 'validate' )
			->willReturn( [] );

		$schemaFactory = $this->createMock( SchemaFactory::class );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchema' )
			->willReturn( $schema );

		$schemaFactory->expects( $this->any() )
			->method( 'newSchemaValidator' )
			->willReturn( $schemaValidator );

		$title = $this->createMock( Title::class );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$page = $this->createMock( WikiPage::class );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$user = $this->createMock( User::class );

		$parserOptions = $this->createMock( ParserOptions::class );

		$schemaContent = new SchemaContent(
			'Foo'
		);

		$schemaContent->setServices( $schemaFactory );

		$instance = new SchemaContentHandler();
		$page = new PageIdentityValue( 0, 1, 'Foo', PageIdentity::LOCAL );
		$validationParams = new ValidationParams( $page, 0 );

		$status = $instance->validateSave( $schemaContent, $validationParams );
		$this->assertTrue( $status->isOK() );
	}

	public function testSerializationOfClassInstance() {
		$title = $this->createMock( Title::class );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$page = $this->createMock( WikiPage::class );

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$user = $this->createMock( User::class );

		$parserOptions = $this->createMock( ParserOptions::class );

		$schemaContent = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		// Use an actual factory instance to ensure a "real" DB instance is
		// invoked and would force a "RuntimeException: Database serialization
		// may cause problems, since the connection is not restored on wakeup."
		$schemaContent->setServices( new SchemaFactory() );

		$instance = new SchemaContentHandler();
		$page = new PageIdentityValue( 0, 1, 'Foo', PageIdentity::LOCAL );
		$validationParams = new ValidationParams( $page, 0 );

		$instance->validateSave( $schemaContent, $validationParams );

		$this->assertIsString( serialize( $instance ) );
	}
}
