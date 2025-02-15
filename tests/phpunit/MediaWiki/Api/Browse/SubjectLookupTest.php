<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\SubjectLookup;

/**
 * @covers \SMW\MediaWiki\Api\Browse\SubjectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SubjectLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SubjectLookup::class,
			new SubjectLookup( $this->store )
		);
	}

	public function testLookup_HTML() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$instance = new SubjectLookup(
			$this->store
		);

		$parameters = [
			'subject' => 'Foo',
			'ns' => NS_MAIN,
			'options' => [],
			'type' => 'html'
		];

		$res = $instance->lookup( $parameters );

		$this->assertArrayHasKey(
			'query',
			$res
		);

		$this->assertArrayHasKey(
			'meta',
			$res
		);
	}

	public function testLookup_JSON() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$instance = new SubjectLookup(
			$this->store
		);

		$parameters = [
			'subject' => 'Foo',
			'ns' => NS_MAIN
		];

		$res = $instance->lookup( $parameters );

		$this->assertArrayHasKey(
			'query',
			$res
		);

		$this->assertArrayHasKey(
			'meta',
			$res
		);
	}

}
