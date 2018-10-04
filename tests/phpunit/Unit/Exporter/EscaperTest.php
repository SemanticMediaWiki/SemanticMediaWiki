<?php

namespace SMW\Tests\Exporter;

use SMW\DIWikiPage;
use SMW\Exporter\Escaper;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Exporter\Escaper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class EscaperTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->addConfiguration( 'smwgExportResourcesAsIri', false );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider encodePageProvider
	 */
	public function testEncodePage( $page, $expected ) {

		$this->assertSame(
			$expected,
			Escaper::encodePage( $page )
		);
	}


	/**
	 * @dataProvider encodeUriProvider
	 */
	public function testEncodeUri( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			Escaper::encodeUri( $uri )
		);

		$this->assertEquals(
			$uri,
			Escaper::decodeUri( Escaper::encodeUri( $uri ) )
		);
	}

	/**
	 * @dataProvider decodeUriProvider
	 */
	public function testDecodeUri( $uri, $expected ) {

		$this->assertEquals(
			$expected,
			Escaper::decodeUri( $uri )
		);

		$this->assertEquals(
			$uri,
			Escaper::encodeUri( Escaper::decodeUri( $uri ) )
		);
	}

	public function encodeUriProvider() {

		$provider[] = [
			'Foo:"&+!%#',
			'Foo-3A-22-26-2B-21-25-23'
		];

		$provider[] = [
			"Foo'-'",
			'Foo-27-2D-27'
		];
		return $provider;
	}

	public function decodeUriProvider() {

		$provider[] = [
			'Foo-3A-22-26-2B-21-25-23',
			'Foo:"&+!%#'
		];

		$provider[] = [
			'Foo-27-2D-27',
			"Foo'-'"
		];

		return $provider;
	}

	public function encodePageProvider() {

		#0
		$provider[] = [
			new DIWikiPage( 'Foo', NS_MAIN, '', '' )
			, 'Foo'
		];

		#1
		$provider[] = [
			new DIWikiPage( 'Foo_bar', NS_MAIN, '', '' ),
			'Foo_bar'
		];

		#2
		$provider[] = [
			new DIWikiPage( 'Foo%bar', NS_MAIN, '', '' ),
			'Foo-25bar'
		];

		#3 / #759
		$provider[] = [
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', '' ),
			'bar-3AFoo'
		];

		#4 / #759
		$provider[] = [
			new DIWikiPage( 'Foo', NS_MAIN, 'bar', 'yuu' ),
			'bar-3AFoo'
		];

		#5
		$provider[] = [
			new DIWikiPage( 'Fooºr', NS_MAIN, '', '' ),
			'Foo-C2-BAr'
		];

		#6
		$provider[] = [
			new DIWikiPage( 'Fooºr', SMW_NS_PROPERTY, '', '' ),
			'Property-3AFoo-C2-BAr'
		];

		#7
		$provider[] = [
			new DIWikiPage( 'Fooºr', NS_CATEGORY, '', '' ),
			'Category-3AFoo-C2-BAr'
		];

		return $provider;
	}

}
