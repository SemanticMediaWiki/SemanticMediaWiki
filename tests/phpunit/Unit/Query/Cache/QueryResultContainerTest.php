<?php

namespace SMW\Tests\Unit\Query\Cache;

use PHPUnit\Framework\TestCase;
use SMW\Query\Cache\QueryResultContainer;

/**
 * @covers \SMW\Query\Cache\QueryResultContainer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class QueryResultContainerTest extends TestCase {

	public function testGetIdAndData() {
		$instance = new QueryResultContainer( 'mw:smw:query:store:abc', [ 'count' => 3 ] );

		$this->assertSame( 'mw:smw:query:store:abc', $instance->getId() );
		$this->assertSame( [ 'count' => 3 ], $instance->getData() );
	}

	public function testGetReturnsFalseOnAbsentKey() {
		$instance = new QueryResultContainer( 'id' );

		// The false sentinel distinguishes a miss from a stored falsy value.
		$this->assertFalse( $instance->get( 'results' ) );
	}

	public function testHas() {
		$instance = new QueryResultContainer( 'id', [ 'results' => [], 'continue' => false, 'nothing' => null ] );

		$this->assertTrue( $instance->has( 'results' ) );
		// A stored null is still "present" (array_key_exists, not isset).
		$this->assertTrue( $instance->has( 'nothing' ) );
		$this->assertFalse( $instance->has( 'absent' ) );
	}

	public function testSetAndGet() {
		$instance = new QueryResultContainer( 'id' );

		$instance->set( 'count', 0 );
		$instance->set( 'continue', false );

		// Stored falsy values round-trip as themselves, not as the miss sentinel.
		$this->assertSame( 0, $instance->get( 'count' ) );
		$this->assertFalse( $instance->get( 'continue' ) );
		$this->assertTrue( $instance->has( 'count' ) );
	}

	public function testExpiryCastsToInt() {
		$instance = new QueryResultContainer( 'id' );

		$this->assertSame( 0, $instance->getExpiry() );

		$instance->setExpiryInSeconds( '3600' );

		$this->assertSame( 3600, $instance->getExpiry() );
	}

	public function testLinkedList() {
		$instance = new QueryResultContainer( 'id' );

		// Empty by default.
		$this->assertSame( [], $instance->getLinkedList() );

		$instance->addToLinkedList( 'q1' );
		$instance->addToLinkedList( 'q2' );
		// Re-adding the same id is idempotent (key set).
		$instance->addToLinkedList( 'q1' );

		$this->assertSame( [ 'q1', 'q2' ], $instance->getLinkedList() );
	}

	public function testLinkedListUsesFrozenMagicKeyInPayload() {
		$instance = new QueryResultContainer( 'id' );
		$instance->addToLinkedList( 'q1' );

		// The dependent-id set is stored under the frozen '@linkedList' key so
		// it survives the serialize() round-trip the store performs.
		$this->assertSame(
			[ '@linkedList' => [ 'q1' => true ] ],
			$instance->getData()
		);
	}

	public function testReadsLinkedListWrittenByTheFormerStore() {
		// A payload as the former Onoi container serialized it.
		$instance = new QueryResultContainer( 'id', [ '@linkedList' => [ 'a42b' => true ] ] );

		$this->assertSame( [ 'a42b' ], $instance->getLinkedList() );
	}

}
