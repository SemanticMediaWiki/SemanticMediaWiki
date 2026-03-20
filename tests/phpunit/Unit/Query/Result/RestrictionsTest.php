<?php

namespace SMW\Tests\Unit\Query\Result;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Query\PrintRequest;
use SMW\Query\Result\Restrictions;

/**
 * @covers \SMW\Query\Result\Restrictions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class RestrictionsTest extends TestCase {

	private $dataItemFactory;
	private $store;
	private $printRequest;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();

		$this->printRequest = $this->getMockBuilder( PrintRequest::class )
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
		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturnCallback( static function ( $param ) {
				return [ 'limit' => 2, 'offset' => 1 ][$param] ?? null;
			} );

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
		$this->printRequest->expects( $this->any() )
			->method( 'getParameter' )
			->willReturnCallback( static function ( $param ) {
				return $param === 'order' ? 'desc' : null;
			} );

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
