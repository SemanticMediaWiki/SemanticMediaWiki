<?php

namespace SMW\Tests\Query\Result;

use SMW\DataItemFactory;
use SMW\Query\Result\Restrictions;

/**
 * @covers SMW\Query\Result\Restrictions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RestrictionsTest extends \PHPUnit\Framework\TestCase {

	private $dataItemFactory;
	private $store;
	private $printRequest;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();

		$this->printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Restrictions::class,
			new Restrictions()
		);
	}

	public function testApplyLimitRestriction() {
		$this->printRequest->expects( $this->at( 0 ) )
			->method( 'getParameter' )
			->with( 'limit' )
			->willReturn( 2 );

		$this->printRequest->expects( $this->at( 1 ) )
			->method( 'getParameter' )
			->with( 'offset' )
			->willReturn( 1 );

		$content = [
			$this->dataItemFactory->newDIWikiPage( 'Foo' ),
			$this->dataItemFactory->newDIWikiPage( 'Bar' ),
			$this->dataItemFactory->newDIWikiPage( 'Foobar' )
		];

		$expected = [
			$this->dataItemFactory->newDIWikiPage( 'Bar' ),
			$this->dataItemFactory->newDIWikiPage( 'Foobar' )
		];

		$this->assertEquals(
			$expected,
			Restrictions::applyLimitRestriction( $this->printRequest, $content )
		);
	}

	public function testApplySortRestriction() {
		$this->printRequest->expects( $this->at( 0 ) )
			->method( 'getParameter' )
			->with( 'order' )
			->willReturn( 'desc' );

		$content = [
			$this->dataItemFactory->newDIWikiPage( 'Foo' ),
			$this->dataItemFactory->newDIWikiPage( 'Bar' ),
			$this->dataItemFactory->newDIWikiPage( 'Yoobar' )
		];

		$expected = [
			$this->dataItemFactory->newDIWikiPage( 'Yoobar' ),
			$this->dataItemFactory->newDIWikiPage( 'Foo' ),
			$this->dataItemFactory->newDIWikiPage( 'Bar' ),
		];

		foreach ( $expected as $di ) {
			$di->getSortKey();
		}

		$this->assertEquals(
			$expected,
			Restrictions::applySortRestriction( $this->printRequest, $content )
		);
	}

}
