<?php

namespace SMW\Tests\Schema;

use SMW\Schema\Rule;

/**
 * @covers \SMW\Schema\Rule
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class RuleTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceof(
			Rule::class,
			new Rule()
		);
	}

	public function testFilterScore() {
		$instance = new Rule();

		$this->assertSame(
			0,
			$instance->filterScore
		);

		$instance->incrFilterScore();

		$this->assertSame(
			1,
			$instance->filterScore
		);
	}

	public function testIf() {
		$data = [
			'if' => [
				'foo',
				'bar' => [
					'foobar'
				]
			]
		];

		$instance = new Rule(
			$data
		);

		$this->assertSame(
			'',
			$instance->if( 'foo' )
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->if( 'bar' )
		);
	}

	public function testThen() {
		$data = [
			'then' => [
				'foo',
				'bar' => [
					'foobar'
				]
			]
		];

		$instance = new Rule(
			$data
		);

		$this->assertSame(
			'',
			$instance->then( 'foo' )
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->then( 'bar' )
		);
	}

}
