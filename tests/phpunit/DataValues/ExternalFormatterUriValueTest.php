<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ExternalFormatterUriValue;

/**
 * @covers \SMW\DataValues\ExternalFormatterUriValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalFormatterUriValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExternalFormatterUriValue::class,
			new ExternalFormatterUriValue()
		);
	}

	public function testTryToParseUserValueOnInvalidUrlFormat() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testTryToParseUserValueOnMissingPlaceholder() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'http://example.org' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasMultiSubstitute() {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( 'http://example.org/Foo?a=$1,$2' );

		$this->assertTrue(
			$instance->hasMultiSubstitute()
		);
	}

	/**
	 * @dataProvider uriProvider
	 */
	public function testGetFormattedUri( $uri, $replacement, $expected ) {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( $uri );

		$this->assertEquals(
			$expected,
			$instance->substituteAndFormatUri( $replacement )
		);
	}

	/**
	 * @dataProvider uriWithParametersProvider
	 */
	public function testFormattedUriWithParameters( $uri, $replacement, $parameters, $expected ) {

		$instance = new ExternalFormatterUriValue();
		$instance->setUserValue( $uri );

		$this->assertEquals(
			$expected,
			$instance->substituteAndFormatUri( $replacement, $parameters )
		);
	}

	public function uriProvider() {

		$provider[] = [
			'http://example.org/$1',
			'foo',
			'http://example.org/foo'
		];

		$provider[] = [
			'http://example.org/$1#Bar<>',
			'foo',
			'http://example.org/foo#Bar%3C%3E'
		];

		$provider[] = [
			'urn:abc:names:def:foo:dtd:xml:$1',
			'foo',
			'urn:abc:names:def:foo:dtd:xml:foo'
		];

	//	$provider[] = array(
	//		'abc:$1',
	//		'foo',
	//		'http://example.org/foo'
	//	);

		$provider[] = [
			'ftp://ftp.is.co.za.example.org/rfc/$1.txt',
			'foo',
			'ftp://ftp.is.co.za.example.org/rfc/foo.txt'
		];

		$provider[] = [
			'gopher://spinaltap.micro.umn.example.edu/00/Weather/California/$1',
			'foo',
			'gopher:spinaltap.micro.umn.example.edu/00/Weather/California/foo'
		];

		$provider[] = [
			'http://www.math.uio.no.example.net/faq/compression-faq/$1',
			'foo',
			'http://www.math.uio.no.example.net/faq/compression-faq/foo'
		];

		$provider[] = [
			'mailto:$1@ifi.unizh.example.gov',
			'foo',
			'mailto:foo@ifi.unizh.example.gov'
		];

		$provider[] = [
			'news:comp.$1.www.servers.unix',
			'foo',
			'news:comp.foo.www.servers.unix'
		];

		$provider[] = [
			'telnet://melvyl.ucop.example.edu/$1',
			'foo',
			'telnet:melvyl.ucop.example.edu/foo'
		];

		$provider[] = [
			'ldap://[2001:db8::7]/c=$1?objectClass?one',
			'foo',
			'ldap:%5B2001:db8::7%5D/c=foo?objectClass?one'
		];

		$provider[] = [
			'mailto:$1.Doe@example.com',
			'foo',
			'mailto:foo.Doe@example.com'
		];

		$provider[] = [
			'tel:+1-816-555-1212',
			'foo',
			''
		];

		$provider[] = [
			'telnet://192.0.2.16:80/$1',
			'foo',
			'telnet:192.0.2.16:80/foo'
		];

		$provider[] = [
			'urn:oasis:names:specification:docbook:dtd:xml:$1',
			'foo',
			'urn:oasis:names:specification:docbook:dtd:xml:foo'
		];

		// https://phabricator.wikimedia.org/T160281
		$provider[] = [
			'http://foo/bar/$1',
			'W%D6LLEKLA01',
			'http://foo/bar/W%D6LLEKLA01'
		];

		// #3386
		$provider[] = [
			'http://foo/bar/$1',
			'A/B/C',
			'http://foo/bar/A/B/C'
		];

		// #...
		$provider[] = [
			'http://foo/bar/$1',
			'A b C',
			'http://foo/bar/A_b_C'
		];

		return $provider;
	}

	public function uriWithParametersProvider() {

		yield [
			'http://example.org/$1',
			'foo',
			[],
			'http://example.org/foo'
		];

		yield [
			'http://example.org/$1&id=$2',
			'foo',
			[ 1001 ],
			'http://example.org/foo&id=1001'
		];

		yield [
			'http://example.org/$1&id=$2',
			'foo',
			[ "a%2Cb" ],
			'http://example.org/foo&id=a,b'
		];
	}

}
