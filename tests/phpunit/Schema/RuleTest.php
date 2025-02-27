<?php

namespace SMW\Tests\Schema;

use SMW\Schema\Rule;

/**
 * @covers \SMW\Schema\Rule
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
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
			null,
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
			null,
			$instance->then( 'foo' )
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->then( 'bar' )
		);
	}

}
