<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\DIProperty;
use SMW\MediaWiki\Api\Browse\PSubjectLookup;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PSubjectLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PSubjectLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PSubjectLookup::class,
			new PSubjectLookup( $this->store )
		);
	}

	/**
	 * @dataProvider lookupProvider
	 */
	public function testLookup( $subject, $parameters, $expected ) {
		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [ $subject ] );

		$instance = new PSubjectLookup(
			$this->store
		);

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

	public function lookupProvider() {
		yield [
			new DIWikiPage( 'Foo bar', NS_MAIN ),
			[
				'search' => 'Foo',
				'property' => 'Bar'
			],
			[
				'Foo bar'
			]
		];

		yield [
			new DIWikiPage( 'Foo bar', NS_HELP ),
			[
				'search' => 'Foo',
				'property' => 'Bar',
				'title-prefix' => false
			],
			[
				'Foo bar'
			]
		];
	}

}
