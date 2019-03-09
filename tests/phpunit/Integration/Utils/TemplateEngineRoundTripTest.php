<?php

namespace SMW\Tests\Integration\Utils;

use SMW\Utils\TempFile;
use SMW\Utils\TemplateEngine;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TemplateEngineRoundTripTest extends \PHPUnit_Framework_TestCase {

	public function testRoundTrip() {

		$contents = '<div>{{abc}}</div><span>{{#ABC}}</span>';
		$tempFile = new TempFile();

		$file = $tempFile->generate( 'Test' );
		$path_parts = pathinfo( $file );

		$tempFile->write( $file, $contents );

		$templateEngine = new TemplateEngine( $path_parts['dirname'] );
		$templateEngine->load( $path_parts['basename'], __METHOD__ );

		$args = [
			'abc' => '123',
			'ABC' => '1001'
		];

		$templateEngine->compile( __METHOD__, $args );

		$this->assertSame(
			'<div>123</div><span>1001</span>',
			$templateEngine->code( __METHOD__ )
		);

		$tempFile->delete( $file );
	}

	public function testRoundTrip_FileWithExtraSlash() {

		$contents = '{{abc}}-{{#ABC}}-{{abc}}';
		$tempFile = new TempFile();

		$file = $tempFile->generate( 'Test' );
		$path_parts = pathinfo( $file );

		$tempFile->write( $file, $contents );

		$templateEngine = new TemplateEngine( $path_parts['dirname'] );
		$templateEngine->load( '/' . $path_parts['basename'], __METHOD__ );

		$args = [
			'abc' => '123',
			'ABC' => '9001'
		];

		$templateEngine->compile( __METHOD__, $args );

		$this->assertSame(
			'123-9001-123',
			$templateEngine->code( __METHOD__ )
		);

		$tempFile->delete( $file );
	}

}
