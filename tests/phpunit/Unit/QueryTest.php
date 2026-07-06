<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\Query\Language\Description;
use SMW\Query\PrintRequest;
use SMW\Query\Query;

/**
 * @covers \SMW\Query\Query
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class QueryTest extends TestCase {

	private $smwgQMaxLimit;
	private $smwgQMaxInlineLimit;

	protected function setUp(): void {
		parent::setUp();

		$this->smwgQMaxLimit = $GLOBALS['smwgQMaxLimit'];
		$this->smwgQMaxInlineLimit = $GLOBALS['smwgQMaxInlineLimit'];
	}

	public function testCanConstruct() {
		$description = $this->getMockForAbstractClass( Description::class );

		$this->assertInstanceOf(
			Query::class,
			new Query( $description )
		);
	}

	public function testSetGetLimitForLowerbound() {
		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new Query( $description, Query::INLINE_QUERY );

		$lowerboundLimit = 1;

		$this->assertGreaterThan(
			$lowerboundLimit,
			$this->smwgQMaxLimit
		);

		$this->assertGreaterThan(
			$lowerboundLimit,
			$this->smwgQMaxInlineLimit
		);

		$instance->setLimit( $lowerboundLimit, true );

		$this->assertEquals(
			$lowerboundLimit,
			$instance->getLimit()
		);

		$instance->setLimit( $lowerboundLimit, false );

		$this->assertEquals(
			$lowerboundLimit,
			$instance->getLimit()
		);
	}

	public function testSetGetLimitForUpperboundWhereLimitIsRestrictedByGLOBALRequirements() {
		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new Query( $description, Query::INLINE_QUERY );

		$upperboundLimit = 999999999;

		$this->assertLessThan(
			$upperboundLimit,
			$this->smwgQMaxLimit
		);

		$this->assertLessThan(
			$upperboundLimit,
			$this->smwgQMaxInlineLimit
		);

		$instance->setLimit( $upperboundLimit, true );

		$this->assertEquals(
			$this->smwgQMaxInlineLimit,
			$instance->getLimit()
		);

		$instance->setLimit( $upperboundLimit, false );

		$this->assertEquals(
			$this->smwgQMaxLimit,
			$instance->getLimit()
		);
	}

	public function testSetGetLimitForUpperboundWhereLimitIsUnrestricted() {
		$description = $this->getMockForAbstractClass( Description::class );

		$instance = new Query( $description, Query::INLINE_QUERY );

		$upperboundLimit = 999999999;

		$this->assertLessThan(
			$upperboundLimit,
			$this->smwgQMaxLimit
		);

		$this->assertLessThan(
			$upperboundLimit,
			$this->smwgQMaxInlineLimit
		);

		$instance->setUnboundLimit( $upperboundLimit );

		$this->assertEquals(
			$upperboundLimit,
			$instance->getLimit()
		);
	}

	public function testToArray() {
		$description = $this->getMockForAbstractClass( Description::class );

		$printRequest = $this->getMockBuilder( PrintRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getSerialisation' )
			->willReturn( '?Foo' );

		$instance = new Query( $description, Query::INLINE_QUERY );
		$instance->setExtraPrintouts( [ $printRequest ] );

		$serialized = $instance->toArray();

		$this->assertIsArray(

			$serialized
		);

		$expected = [
			'conditions',
			'parameters',
			'printouts'
		];

		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $serialized );
		}

		$expectedParameters = [
			'limit',
			'offset',
			'mainlabel',
			'sortkeys',
			'querymode'
		];

		foreach ( $expectedParameters as $key ) {
			$this->assertArrayHasKey( $key, $serialized['parameters'] );
		}
	}

	public function testGetHash() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getFingerprint' ] )
			->getMockForAbstractClass();

		$instance = new Query( $description, Query::INLINE_QUERY );
		$instance->setLimit( 50 );

		$hash = $instance->getHash();

		$this->assertIsString(

			$hash
		);

		$instance->setLimit( 100 );

		$this->assertNotEquals(
			$hash,
			$instance->getHash()
		);
	}

}
