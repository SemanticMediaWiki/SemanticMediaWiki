<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\SqlFragmentBuilder;

/**
 * @covers \SMW\MediaWiki\Connection\SqlFragmentBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SqlFragmentBuilderTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'" . str_replace( "'", "''", (string)$v ) . "'" );

		$this->connection->method( 'makeList' )
			->willReturnCallback( static fn ( array $v ) => implode( ',', array_map( static fn ( $x ) => "'$x'", $v ) ) );
	}

	public function testEq() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$this->assertSame( "t1.s_id='42'", $builder->eq( 't1.s_id', '42' ) );
	}

	public function testNeqQuotesValueAndEscapesSingleQuotes() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$this->assertSame( "t1.s_id!='4''2'", $builder->neq( 't1.s_id', "4'2" ) );
	}

	public function testNeqWithNull() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$this->assertSame( "t1.s_id!=''", $builder->neq( 't1.s_id', null ) );
	}

	public function testIn() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$this->assertSame(
			"t1.smw_id IN ('1','2','3')",
			$builder->in( 't1.smw_id', [ '1', '2', '3' ] )
		);
	}

	public function testLike() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$this->assertSame( "t1.smw_title LIKE 'Foo%'", $builder->like( 't1.smw_title', 'Foo%' ) );
	}

	public function testAliasAndIndexArePublicForCallbackParity() {
		$builder = new SqlFragmentBuilder( $this->connection );

		$builder->alias = 't';
		$builder->index = 2;

		$this->assertSame( 't', $builder->alias );
		$this->assertSame( 2, $builder->index );
	}

}
