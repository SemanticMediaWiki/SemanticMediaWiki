<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\DIProperty;
use SMW\MediaWiki\Api\Browse\SubjectLookup;

/**
 * @covers \SMW\MediaWiki\Api\Browse\SubjectLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SubjectLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

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
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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
