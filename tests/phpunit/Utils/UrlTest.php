<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Url;

/**
 * @covers \SMW\Utils\Url
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class UrlTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider urlProvider
	 */
	public function testGet( $url, $flags, $expected ) {

		$instance = new Url( $url );

		$this->assertEquals(
			$expected,
			$instance->get( ...$flags )
		);
	}

	/**
	 * @dataProvider pathProvider
	 */
	public function testPath( $url, $path, $expected ) {

		$instance = new Url( $url );

		$this->assertEquals(
			$expected,
			$instance->path( $path )
		);
	}

	public function urlProvider() {

		yield [
			'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
			[ PHP_URL_SCHEME, PHP_URL_HOST ],
			'http://example.com'
		];

		yield [
			'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
			[ PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PORT ],
			'http://usr:pss@example.com:81'
		];

		yield [
			'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
			[ PHP_URL_QUERY ],
			'?a=b&b[]=2&b[]=3'
		];

		yield 'invalid' => [
			'http://:80',
			[ PHP_URL_SCHEME, PHP_URL_HOST ],
			''
		];
	}

	public function pathProvider() {

		yield [
			'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
			'',
			'http://usr:pss@example.com:81/mypath/myfile.html'
		];

		yield [
			'http://usr:pss@example.com:81/mypath/myfile.html?a=b&b[]=2&b[]=3#myfragment',
			'/foo/bar',
			'http://usr:pss@example.com:81/foo/bar'
		];
	}

}
