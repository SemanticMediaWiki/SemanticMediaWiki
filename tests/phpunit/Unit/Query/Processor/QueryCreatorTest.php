<?php

namespace SMW\Tests\Unit\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\Query;
use SMW\QueryFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @covers SMW\Query\Processor\QueryCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreatorTest extends TestCase {

	public function testCanConstruct() {
		$queryFactory = $this->getMockBuilder( QueryFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QueryCreator::class,
			new QueryCreator( $queryFactory )
		);
	}

	/**
	 * @dataProvider queryStringProvider
	 */
	public function testCreate( $queryString, $params, $expected ) {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( $queryString, $params );

		$this->assertInstanceOf(
			Query::class,
			$query
		);

		$this->assertSame(
			$expected,
			$query->toString()
		);
	}

	public function queryStringProvider() {
		$provider[] = [
			'[[Foo::Bar]]',
			[
				'limit'  => 42,
				'offset' => 12
			],
			'[[Foo::Bar]]|limit=42|offset=12|mainlabel='
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'source'    => 'foobar',
				'mainLabel' => 'Some'
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=Some|source=foobar'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ '', 'SomeA', 'SomeB' ],
				'order' => [ 'desc', 'random', 'asc' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=SomeA,SomeB|order=random,asc'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ ',' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=,|order=asc'
		];

		return $provider;
	}

	public function testCursorParamWithDefaultSortDecodesAndAppliesPayload(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		// base64url of {"v":1,"sort":"Foo","id":42}
		$token = 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ';

		$query = $instance->create( '[[Foo::Bar]]', [ 'cursor' => $token ] );

		$this->assertSame(
			[ 'v' => 1, 'sort' => 'Foo', 'id' => 42 ],
			$query->getCursorAfter()
		);
	}

	public function testCursorParamWithCustomSortIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'sort'   => [ 'SomeProperty' ],
				'cursor' => 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ',
			]
		);

		// Cursor must NOT be applied when a custom `sort=` is present.
		$this->assertNull( $query->getCursorAfter() );

		// Error is surfaced so the engine can refuse the query. The
		// query parser may also push unrelated parsing errors onto the
		// list (e.g. `smw_noqueryfeature` depending on the test-runner
		// `smwgQFeatures` config), so search the full list for our
		// cursor-specific marker rather than asserting position.
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'Cursor pagination'
		);
	}

	public function testCursorParamWithCountModeIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[
				'queryMode' => Query::MODE_COUNT,
				'cursor'    => 'eyJ2IjoxLCJzb3J0IjoiRm9vIiwiaWQiOjQyfQ',
			]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'`format=count`'
		);
	}

	public function testMalformedCursorTokenIsRejectedWithError(): void {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create(
			'[[Foo::Bar]]',
			[ 'cursor' => '!!!not-a-valid-base64-token!!!' ]
		);

		$this->assertNull( $query->getCursorAfter() );
		$this->assertCursorErrorPresent(
			$query->getErrors(),
			'Malformed'
		);
	}

	public function testEmptyCursorParamDoesNotTriggerCursorMode(): void {
		// Default value for `cursor` is empty string. Absence MUST keep
		// the query on the legacy offset path.
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( '[[Foo::Bar]]', [ 'cursor' => '' ] );

		$this->assertNull( $query->getCursorAfter() );
	}

	private function assertCursorErrorPresent( array $errors, string $marker ): void {
		foreach ( $errors as $err ) {
			if ( is_string( $err ) && str_contains( $err, $marker ) ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}
		$this->fail( "Expected cursor error containing '$marker', got: " . var_export( $errors, true ) );
	}

}
