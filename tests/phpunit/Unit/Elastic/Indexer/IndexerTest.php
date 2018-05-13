<?php

namespace SMW\Tests\Elastic\Indexer;

use SMW\Elastic\Indexer\Indexer;

/**
 * @covers \SMW\Elastic\Indexer\Indexer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Indexer::class,
			new Indexer( $this->store )
		);
	}

	/**
	 * @dataProvider textLinksProvider
	 */
	public function testRemoveLinks( $text, $expected ) {

		$instance = new Indexer( $this->store );

		$this->assertEquals(
			$expected,
			$instance->removeLinks( $text )
		);
	}

	public function textLinksProvider() {

		yield [
			'abc',
			'abc'
		];

		yield [
			'{{DEFAULTSORT: FOO}}',
			''
		];

		yield [
			'{{Foo|bar=foobar}}',
			'bar=foobar'
		];

		yield [
			'[[Has foo::Bar]]',
			'Bar'
		];

		yield [
			'[[:foo|abc]]',
			'abc'
		];

		yield [
			'abc [[:foo|abc]]',
			'abc abc'
		];

		yield [
			'[[:|abc]]',
			'[[:|abc]]'
		];

		yield [
			'[[:abc]]',
			':abc'
		];

		yield [
			'abc [[abc]]',
			'abc abc'
		];

		yield [
			'[[abc]] abc [[:bar|foobar]]',
			'abc abc foobar'
		];

		yield [
			'[[:Spécial%3ARequêter&cl=Yzo1jUEKwzAMBF8T3RKMS486tPTQb8iJjE3sGCSH9PlVGgpzWLRi9oPj_Wm8SUfvtr0GluH2OPF2fxkQm1TqGKTR0ikUhpJr7uids7StSKVAYlpYFDW1A5RJ5lQocMFpmgbv4i49mdo7Yd1LV5gLqb03-SmtOPKa_1nrcS2dPUITcyPpDC1G5Y4OKuXtGvgC|foo]]',
			'foo'
		];
	}
}
