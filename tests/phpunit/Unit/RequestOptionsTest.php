<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\RequestOptions;
use SMW\StringCondition;

/**
 * @covers \SMW\RequestOptions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class RequestOptionsTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RequestOptions::class,
			new RequestOptions()
		);
	}

	public function testAddStringCondition() {
		$instance = new RequestOptions();
		$instance->addStringCondition( 'Foo', StringCondition::STRCOND_PRE );

		foreach ( $instance->getStringConditions() as $stringCondition ) {
			$this->assertInstanceOf(
				StringCondition::class,
				$stringCondition
			);

			$this->assertFalse(
				$stringCondition->isOr
			);
		}

		$this->assertEquals(
			'[-1,0,0,false,true,null,true,false,"Foo#0##",[],[],null,null]',
			$instance->getHash()
		);
	}

	public function testEddExtraCondition() {
		$instance = new RequestOptions();
		$instance->addExtraCondition( 'Foo' );
		$instance->addExtraCondition( [ 'Bar' => 'Foobar' ] );

		$this->assertEquals(
			[
				'Foo',
				[ 'Bar' => 'Foobar' ]
			],
			$instance->getExtraConditions()
		);

		$this->assertEquals(
			'[-1,0,0,false,true,null,true,false,"",["Foo",{"Bar":"Foobar"}],[],null,null]',
			$instance->getHash()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testLimit( $limit, $expected ) {
		$instance = new RequestOptions();
		$instance->setLimit( $limit );

		$this->assertEquals(
			$expected,
			$instance->getLimit()
		);

		$instance->limit = $limit;

		$this->assertEquals(
			$expected,
			$instance->getLimit()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testOffset( $offset, $expected ) {
		$instance = new RequestOptions();
		$instance->setOffset( $offset );

		$this->assertEquals(
			$expected,
			$instance->getOffset()
		);

		$instance->offset = $offset;

		$this->assertEquals(
			$expected,
			$instance->getOffset()
		);
	}

	public function numberProvider() {
		$provider[] = [
			42,
			42
		];

		$provider[] = [
			'42foo',
			42
		];

		return $provider;
	}

	public function testCursorAfterRoundTrip() {
		$options = new RequestOptions();
		$this->assertNull( $options->getCursorAfter() );

		$options->setCursorAfter( 123 );

		$this->assertSame( 123, $options->getCursorAfter() );
		$this->assertNull( $options->getCursorBefore() );
		$this->assertTrue( $options->hasCursor() );
	}

	public function testCursorBeforeRoundTrip() {
		$options = new RequestOptions();

		$options->setCursorBefore( 456 );

		$this->assertSame( 456, $options->getCursorBefore() );
		$this->assertNull( $options->getCursorAfter() );
		$this->assertTrue( $options->hasCursor() );
	}

	public function testFirstAndLastCursorRoundTrip() {
		$options = new RequestOptions();
		$this->assertNull( $options->getFirstCursor() );
		$this->assertNull( $options->getLastCursor() );

		$options->setFirstCursor( 1 );
		$options->setLastCursor( 99 );

		$this->assertSame( 1, $options->getFirstCursor() );
		$this->assertSame( 99, $options->getLastCursor() );
	}

	public function testHasCursorReturnsFalseByDefault() {
		$options = new RequestOptions();
		$this->assertFalse( $options->hasCursor() );
	}

	public function testHashIncludesCursorData() {
		$a = new RequestOptions();
		$b = new RequestOptions();
		$b->setCursorAfter( 1 );

		$this->assertNotSame( $a->getHash(), $b->getHash() );
	}

	/**
	 * Guards against silent value-cache collisions: any newly added property
	 * must be consciously classified as value-affecting (folded into
	 * getValueHash) or explicitly excluded with a reason.
	 */
	public function testEveryDeclaredPropertyIsClassifiedForValueHash() {
		// Folded into getValueHash() as value identity.
		$valueAffecting = [
			'limit', 'offset', 'sort', 'ascending', 'boundary', 'include_boundary',
			'conditionConstraint', 'natural', 'cursorAfter', 'cursorBefore',
			'stringConditions', 'extraConditions',
		];

		// Deliberately excluded, each for a documented reason.
		$excluded = [
			'options', // included via the NON_VALUE_OPTIONS denylist, not by name
			'exclude_limit', // forced true across the prefetch path (a constant there)
			'lookahead', // normalized to a fixed value by the prefetch machinery
			'isChain', // encoded by PrefetchCache::makeCacheKey() markers
			'isFirstChain', // encoded by PrefetchCache::makeCacheKey() markers
			'caller', // SQL-comment telemetry only
			'firstCursor', // output metadata written back by the lookup
			'lastCursor', // output metadata written back by the lookup
			'cursorHasMore', // output metadata written back by the lookup
		];

		$classified = array_merge( $valueAffecting, $excluded );

		foreach ( ( new ReflectionClass( RequestOptions::class ) )->getProperties() as $property ) {
			if ( $property->isStatic() ) {
				continue;
			}

			$this->assertContains(
				$property->getName(),
				$classified,
				"New RequestOptions property '{$property->getName()}' must be classified in " .
				'getValueHash(): add it to the value tuple or to the documented excluded list.'
			);
		}
	}

	public function testGetValueHashIgnoresExecutionHintsAndNormalizedFields() {
		$base = new RequestOptions();

		$withHints = new RequestOptions();
		$withHints->exclude_limit = true;
		$withHints->lookahead = 5;
		$withHints->setOption( RequestOptions::PREFETCH_FINGERPRINT, 'subject-set' );
		$withHints->setOption( 'NO_GROUPBY', true );
		$withHints->setOption( 'NO_DISTINCT', true );
		$withHints->setOption( 'ORDER BY', 'smw_sort' );
		$withHints->setOption( 'GROUP BY', 'smw_id' );
		$withHints->setOption( 'DISTINCT', true );

		$this->assertSame(
			$base->getValueHash(),
			$withHints->getValueHash()
		);
	}

	public function testGetValueHashDistinguishesValueAffectingOption() {
		$base = new RequestOptions();

		$withOption = new RequestOptions();
		$withOption->setOption( RequestOptions::CONDITION_CONSTRAINT_RESULT, true );

		$this->assertNotSame(
			$base->getValueHash(),
			$withOption->getValueHash()
		);
	}

	public function testGetValueHashDistinguishesSortDirection() {
		$ascending = new RequestOptions();
		$ascending->sort = true;
		$ascending->ascending = true;

		$descending = new RequestOptions();
		$descending->sort = true;
		$descending->ascending = false;

		$this->assertNotSame(
			$ascending->getValueHash(),
			$descending->getValueHash()
		);
	}

	public function testGetValueHashIsOptionOrderIndependent() {
		$a = new RequestOptions();
		$a->setOption( 'alpha', 1 );
		$a->setOption( 'beta', 2 );

		$b = new RequestOptions();
		$b->setOption( 'beta', 2 );
		$b->setOption( 'alpha', 1 );

		$this->assertSame(
			$a->getValueHash(),
			$b->getValueHash()
		);
	}

}
