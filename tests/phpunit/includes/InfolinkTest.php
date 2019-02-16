<?php

namespace SMW\Test;

use SMW\Tests\TestEnvironment;
use SMWInfolink as Infolink;

/**
 * @covers \SMWInfolink
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InfolinkTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment(
			[
				'wgContLang' => \Language::factory( 'en' )
			]
		);
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testEncodeParameters_ForTitle( array $params, array $expected ) {

		$encodeResult = Infolink::encodeParameters( $params, true );

		$this->assertEquals(
			$expected[0],
			$encodeResult
		);
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testEncodeParameters_NotForTitle( array $params, array $expected ) {

		$encodeResult = Infolink::encodeParameters( $params, false );

		$this->assertEquals(
			$expected[1],
			$encodeResult
		);
	}

	public function testNewPropertySearchLink_GetText() {

		$instance = Infolink::newPropertySearchLink( 'Foo', 'Bar', 'Foobar' );

		$instance->setCompactLink( false );

		$this->assertContains(
			'title=Special:SearchByProperty&x=%3ABar%2FFoobar',
			$instance->getText( SMW_OUTPUT_RAW )
		);

		$instance->setCompactLink( true );

		$this->assertContains(
			'title=Special:SearchByProperty&cl=OkJhci9Gb29iYXI',
			$instance->getText( SMW_OUTPUT_RAW )
		);

		$this->assertEquals(
			$instance->getURL(),
			$instance->getText( SMW_OUTPUT_RAW )
		);
	}

	public function testGetURL() {

		$instance = new Infolink( true, 'Foo', 'Bar/Foobar' );
		$instance->setCompactLink( true );

		$this->assertContains(
			'/Bar/Foobar',
			$instance->getLocalURL()
		);

		$this->assertContains(
			'/Bar/Foobar',
			$instance->getURL()
		);

		$instance->setParameter( 123, 'foo' );

		$this->assertContains(
			'title=Bar/Foobar&cl=Zm9vPTEyMw',
			$instance->getLocalURL()
		);

		$this->assertContains(
			'title=Bar/Foobar&cl=Zm9vPTEyMw',
			$instance->getURL()
		);

		$instance->setCompactLink( false );

		$this->assertContains(
			'title=Bar/Foobar&foo=123',
			$instance->getLocalURL()
		);

		$this->assertContains(
			'title=Bar/Foobar&foo=123',
			$instance->getURL()
		);
	}

	/**
	 * @dataProvider base64Provider
	 */
	public function testEncodeBase64( $source, $target ) {

		$this->assertContains(
			$target,
			Infolink::encodeCompactLink( $source )
		);
	}

	/**
	 * @dataProvider base64Provider
	 */
	public function testEncodeDecodeBase64RoundTrip( $source, $target ) {

		$this->assertEquals(
			$source,
			Infolink::decodeCompactLink( Infolink::encodeCompactLink( $source ) )
		);
	}

	/**
	 * @dataProvider base64DecodeProvider
	 */
	public function testDecodeBase64( $source, $target ) {

		$this->assertEquals(
			$source,
			Infolink::decodeCompactLink( $target )
		);
	}

	public function testNotDecodable() {

		$this->assertNotContains(
			'%3ABar/Foobar',
			Infolink::decodeCompactLink( 'eD0lM0FCYXIlMkZGb29iYXI' )
		);
	}

	public function base64Provider() {

		yield [
			'%3ABar/Foobar',
			'cl:JTNBQmFyL0Zvb2Jhcg'
		];

		yield [
			'-5B-5BProperty%3A%2B-5D-5D-20-5B-5BCategory%3ALorem-20ipsum-5D-5D/-3FHas-20description%3DDescription/-3FHas-20type/mainlabel=/format=table/class=datatable/sort=/order=asc/offset=100/limit=50',
			'cl:YzpFijEOgzAMRU_DGCUFMXooRVUHhl7BgKkiJTiy3YHbF8qA9Jf33ndt59ruLVxIbKuae1Xvoj9WB_ePDzT6sBxxYKG8h1j0m8-bd83zhbrLmXSSWCzyWjV9f9F1sa2QzxjXhCMl8AtLRgPDMZGfEqrCjIYnK4uBZ5lJAHXyvCxKBrcQfIo5GrThBw'
		];
	}

	public function base64DecodeProvider() {

		yield [
			'%3ABar/Foobar',
			'cl:JTNBQmFyL0Zvb2Jhcg'
		];

		yield [
			'%3ABar/Foobar',
			'%3ABar/Foobar'
		];

		yield [
			'-5B-5BProperty%3A%2B-5D-5D-20-5B-5BCategory%3ALorem-20ipsum-5D-5D/-3FHas-20description%3DDescription/-3FHas-20type/mainlabel=/format=table/class=datatable/sort=/order=asc/offset=100/limit=50',
			'cl:YzpNijEOgzAMRU-TbJVSEKOHtgh16NArGDBVpARHtiuV2zeIBekv773_g0t3r3sLFxLbXHtzTRX9viYc8YFGH5Y9vlgo1xCLfvNxc81waYcnatUz6SSxWOTVtX1_otPJtkI-Y1wTjpTALywZDQzHRH5KqAozGh6sLAaeZSYB1MnzsigZXEPwKeZo0IU'
		];

		yield [
			'-5B-5BProperty%3A%2B-5D-5D-20-5B-5BCategory%3ALorem-20ipsum-5D-5D/-3FHas-20description%3DDescription/-3FHas-20type/mainlabel=/format=table/class=datatable/sort=/order=asc/offset=100/limit=50',
			'cl:YzpFijEOgzAMRU_DGCUFMXooRVUHhl7BgKkiJTiy3YHbF8qA9Jf33ndt59ruLVxIbKuae1Xvoj9WB_ePDzT6sBxxYKG8h1j0m8-bd83zhbrLmXSSWCzyWjV9f9F1sa2QzxjXhCMl8AtLRgPDMZGfEqrCjIYnK4uBZ5lJAHXyvCxKBrcQfIo5GrThBw'
		];
	}

	public function parameterDataProvider() {
		return [
			[
				// #0
				[
					'format=template',
					'link=none'
				],
				[
					'format=template/link=none',
					'x=format%3Dtemplate%2Flink%3Dnone'
				]
			],

			// #1 Bug 47010 (space encoding, named args => named%20args)
			[
				[
					'format=template',
					'link=none',
					'named args=1'
				],
				[
					'format=template/link=none/named-20args=1',
					'x=format%3Dtemplate%2Flink%3Dnone%2Fnamed-20args%3D1'
				]
			],

			// #2 "\"
			[
				[
					"format=foo\bar",
				],
				[
					'format=foo-5Cbar',
					'x=format%3Dfoo-5Cbar'
				]
			],
		];
	}

}
