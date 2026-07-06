<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
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

}
