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
class TemplateEngineTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testLoad_ThrowsException() {

		$instance = new TemplateEngine();

		$this->setExpectedException( '\SMW\Exception\FileNotReadableException' );
		$instance->load( 'foo', 'bar' );
	}

	public function testCode_ThrowsException() {

		$instance = new TemplateEngine();

		$this->setExpectedException( '\RuntimeException' );
		$instance->publish( 'Foo' );
	}

	public function testLoad() {

		$instance = new TemplateEngine( SMW_PHPUNIT_DIR );
		$instance->load( '/Fixtures/readable.file', 'Foo' );

		$instance->compile( 'Foo', [] );

		$this->assertEquals(
			'Foo',
			$instance->publish( 'Foo' )
		);
	}

	public function testBulkLoad() {

		$instance = new TemplateEngine( SMW_PHPUNIT_DIR );
		$instance->clearTemplates();

		$instance->bulkLoad( [ '/Fixtures/readable.file' => 'Foo' ] );
		$instance->compile( 'Foo', [] );

		$this->assertEquals(
			'Foo',
			$instance->publish( 'Foo' )
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
