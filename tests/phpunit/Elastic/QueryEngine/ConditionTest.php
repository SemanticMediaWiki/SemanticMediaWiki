<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\Condition;

/**
 * @covers \SMW\Elastic\QueryEngine\Condition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConditionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Condition::class,
			new Condition()
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testResolveConditionParameters( $paramsters, $type, $expected ) {

		$instance = new Condition( $paramsters );
		$instance->type( $type );

		$this->assertEquals(
			$expected,
			$instance->__toString()
		);
	}

	/**
	 * @dataProvider typeProvider
	 */
	public function testType( $type, $expected ) {

		$instance = new Condition( [] );
		$instance->type( $type );

		$this->assertEquals(
			$expected,
			$instance->getType()
		);
	}

	public function testConditionLogs() {

		$cond = new Condition( [ 'foobar' ] );
		$cond->log( 'foo_log' );

		$instance = new Condition( $cond );
		$instance->log( [ 'test' ] );

		$instance->toArray();

		$this->assertEquals(
			[ [ 'test' ], [ 'foo_log' ] ],
			$instance->getLogs()
		);
	}

	public function parametersProvider() {

		yield [
			'',
			'',
			'""'
		];

		yield [
			[],
			'',
			'[]'
		];

		yield [
			[ 'Foo' => [ 'Bar' ] ],
			'must',
			'{"bool":{"must":{"Foo":["Bar"]}}}'
		];

		yield [
			[ new Condition( [ 'foobar' ] ) ],
			'must',
			'{"bool":{"must":[{"bool":{"must":["foobar"]}}]}}'
		];

		$cond = new Condition( [ 'foobar' ] );
		$cond->type( '' );

		yield [
			[ $cond ],
			'must',
			'{"bool":{"must":[["foobar"]]}}'
		];

		$cond = new Condition( [ 'foobar' ] );
		$cond->type( null );

		yield [
			[ $cond ],
			'must',
			'{"bool":{"must":[["foobar"]]}}'
		];

		yield [
			[ [ new Condition( [ 'foobar' ] ), new Condition( [ 'bar' ] ) ] ],
			Condition::TYPE_SHOULD,
			'{"bool":{"should":[[{"bool":{"must":["foobar"]}},{"bool":{"must":["bar"]}}]]}}'
		];
	}

	public function typeProvider() {

		yield [
			'must',
			Condition::TYPE_MUST
		];

		yield [
			'must_not',
			Condition::TYPE_MUST_NOT
		];

		yield [
			'should',
			Condition::TYPE_SHOULD
		];

		yield [
			'filter',
			Condition::TYPE_FILTER
		];
	}

}
