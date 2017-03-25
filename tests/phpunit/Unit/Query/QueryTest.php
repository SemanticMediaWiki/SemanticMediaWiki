<?php

namespace SMW\Tests;

use SMWQuery as Query;

/**
 * @covers \SMWQuery
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryTest extends \PHPUnit_Framework_TestCase {

	private $smwgQMaxLimit;
	private $smwgQMaxInlineLimit;

	protected function setUp() {
		parent::setUp();

		$this->smwgQMaxLimit = $GLOBALS['smwgQMaxLimit'];
		$this->smwgQMaxInlineLimit = $GLOBALS['smwgQMaxInlineLimit'];
	}

	public function testCanConstruct() {

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$this->assertInstanceOf(
			'\SMWQuery',
			new Query( $description )
		);
	}

	public function testSetGetLimitForLowerbound() {

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

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

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

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

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

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

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$printRequest = $this->getMockBuilder( 'SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Query( $description, Query::INLINE_QUERY );
		$instance->setExtraPrintouts( [ $printRequest ] );

		$serialized = $instance->toArray();

		$this->assertInternalType(
			'array',
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

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->setMethods( [ 'getFingerprint' ] )
			->getMockForAbstractClass();

		$instance = new Query( $description, Query::INLINE_QUERY );
		$instance->setLimit( 50 );

		$hash = $instance->getHash();

		$this->assertInternalType(
			'string',
			$hash
		);

		$instance->setLimit( 100 );

		$this->assertNotEquals(
			$hash,
			$instance->getHash()
		);
	}

}
