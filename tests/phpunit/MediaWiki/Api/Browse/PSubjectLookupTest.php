<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Api\Browse\PSubjectLookup;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\MediaWiki\Api\Browse\PSubjectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PSubjectLookupTest extends TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
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
			new WikiPage( 'Foo bar', NS_MAIN ),
			[
				'search' => 'Foo',
				'property' => 'Bar'
			],
			[
				'Foo bar'
			]
		];

		yield [
			new WikiPage( 'Foo bar', NS_HELP ),
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
