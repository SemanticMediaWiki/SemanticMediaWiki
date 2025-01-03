<?php

namespace SMW\Tests\Utils;

use SMW\Utils\TemplateEngine;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\TemplateEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TemplateEngineTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testLoad_ThrowsException() {
		$instance = new TemplateEngine();

		$this->expectException( '\SMW\Exception\FileNotReadableException' );
		$instance->load( 'foo', 'bar' );
	}

	public function testCode_ThrowsException() {
		$instance = new TemplateEngine();

		$this->expectException( '\RuntimeException' );
		$instance->publish( 'Foo' );
	}

	public function testLoad() {
		$instance = new TemplateEngine( \SMW_PHPUNIT_DIR );
		$instance->load( '/Fixtures/readable.file', 'Foo' );

		$instance->compile( 'Foo', [] );

		$this->assertEquals(
			'Foo',
			$instance->publish( 'Foo' )
		);
	}

	public function testBulkLoad() {
		$instance = new TemplateEngine( \SMW_PHPUNIT_DIR );
		$instance->clearTemplates();

		$instance->bulkLoad( [ '/Fixtures/readable.file' => 'Foo' ] );
		$instance->compile( 'Foo', [] );

		$this->assertEquals(
			'Foo',
			$instance->publish( 'Foo' )
		);
	}

	public function testHtmlTidy() {
		$instance = new TemplateEngine( \SMW_PHPUNIT_DIR );
		$instance->clearTemplates();

		$instance->setContents( __METHOD__, "{{foo}}<span>\n    </span>" );
		$instance->compile( __METHOD__, [ 'foo' => 'bar' ] );

		$this->assertEquals(
			"bar<span>\n    </span>",
			$instance->publish( __METHOD__ )
		);

		$this->assertEquals(
			"bar<span></span>",
			$instance->publish( __METHOD__, TemplateEngine::HTML_TIDY )
		);
	}

	/**
	 * @dataProvider contentsProvider
	 */
	public function testCompileContents( $contents, $args, $expected ) {
		$instance = new TemplateEngine();

		$instance->setContents( __METHOD__, $contents );
		$instance->compile( __METHOD__, $args );

		$this->assertSame(
			$expected,
			$instance->publish( __METHOD__ )
		);
	}

	public function contentsProvider() {
		yield [
			'abc {{FOO}} 123',
			[
				'FOO' => '_ABC_'
			],
			'abc _ABC_ 123',
		];
	}

}
