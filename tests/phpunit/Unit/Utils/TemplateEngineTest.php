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
		$instance->code( 'Foo' );
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
			$instance->code( __METHOD__ )
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
