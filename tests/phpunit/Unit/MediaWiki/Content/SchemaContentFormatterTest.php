<?php

namespace SMW\Tests\MediaWiki\Content;

use SMW\MediaWiki\Content\SchemaContentFormatter;
use SMW\Schema\Schema;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContentFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ContentFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			SchemaContentFormatter::class,
			new SchemaContentFormatter()
		);
	}

	public function testGetHelpLink() {

		$schema = $this->getMockBuilder( '\SMW\Schema\Schema' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SchemaContentFormatter();

		$this->assertInternalType(
			'string',
			$instance->getHelpLink( $schema )
		);
	}

	public function testGetText() {

		$schema = $this->getMockBuilder( '\SMW\Schema\Schema' )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnCallback( [ $this, 'schema_get' ] ) );

		$text = '...';
		$isYaml = false;
		$errors = [];

		$instance = new SchemaContentFormatter();

		$this->assertInternalType(
			'string',
			$instance->getText( $text, $isYaml, $schema, $errors )
		);
	}

	public function testGetText_Errors() {

		$schema = $this->getMockBuilder( '\SMW\Schema\Schema' )
			->disableOriginalConstructor()
			->getMock();

		$schema->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnCallback( [ $this, 'schema_get' ] ) );

		$text = '...';
		$isYaml = false;

		$errors = [
			[ 'property' => 'foo', 'message' => '---' ]
		];

		$instance = new SchemaContentFormatter();

		$this->assertInternalType(
			'string',
			$instance->getText( $text, $isYaml, $schema, $errors )
		);
	}

	public function schema_get( $key ) {
		return $key === Schema::SCHEMA_TAG ? [] : '';
	}

}
