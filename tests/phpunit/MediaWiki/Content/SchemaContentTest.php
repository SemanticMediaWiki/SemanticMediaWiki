<?php

namespace SMW\Tests\MediaWiki\Content;

use SMW\MediaWiki\Content\SchemaContent;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContent
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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

	public function testPreSaveTransform() {
		$title = $this->createMock( '\Title' );

		$user = $this->createMock( '\User' );

		$parserOptions = $this->createMock( '\ParserOptions' );

		$instance = new SchemaContent(
			json_encode( [ 'Foo' => 42 ] )
		);

		$this->assertInstanceof(
			SchemaContent::class,
			$instance->preSaveTransform( $title, $user, $parserOptions )
		);
	}
}
