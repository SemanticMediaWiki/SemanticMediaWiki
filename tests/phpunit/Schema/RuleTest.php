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
class RuleTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			Rule::class,
			new Rule()
		);
	}

	public function testFilterScore() {

		$instance = new Rule();

		$this->assertEquals(
			0,
			$instance->filterScore
		);

		$instance->incrFilterScore();

		$this->assertEquals(
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

		$this->assertEquals(
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

		$this->assertEquals(
			'',
			$instance->then( 'foo' )
		);

		$this->assertEquals(
			[ 'foobar' ],
			$instance->then( 'bar' )
		);
	}

}
