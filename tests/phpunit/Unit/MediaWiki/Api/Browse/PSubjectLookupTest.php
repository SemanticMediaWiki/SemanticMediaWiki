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
class PSubjectLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

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

	public function testLookup() {

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [ new DIWikiPage( 'Foobar', NS_MAIN ) ] ) );

		$instance = new PSubjectLookup(
			$this->store
		);

		$parameters = [
			'search' => 'Foo',
			'property' => 'Bar'
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$res['query'],
			[
				'Foobar'
			]
		);
	}

}
