<?php

namespace SMW\Tests\Schema\Content;

use SMW\Schema\Content\ContentFormatter;
use SMW\Schema\Schema;

/**
 * @covers \SMW\Schema\Content\ContentFormatter
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
			ContentFormatter::class,
			new ContentFormatter()
		);
	}

	public function testGetHelpLink() {

		$schema = $this->getMockBuilder( '\SMW\Schema\Schema' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ContentFormatter();

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

		$instance = new ContentFormatter();

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

		$instance = new ContentFormatter();

		$this->assertInternalType(
			'string',
			$instance->getText( $text, $isYaml, $schema, $errors )
		);
	}

	public function schema_get( $key ) {
		return $key === Schema::SCHEMA_TAG ? [] : '';
	}

}
