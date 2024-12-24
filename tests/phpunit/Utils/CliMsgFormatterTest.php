<?php

namespace SMW\Tests\Utils;

use SMW\Utils\CliMsgFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\CliMsgFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CliMsgFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testHead() {
		$instance = new CliMsgFormatter();

		$this->assertIsString(

			$instance->head()
		);
	}

	public function testWordwrap() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			'Foo Bar',
			$instance->wordwrap( [ 'Foo', 'Bar' ] )
		);
	}

	public function testProgress() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			'50 %',
			$instance->progress( 5, 10 )
		);
	}

	public function testProgressCompact() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			'5 / 10 ( 50%)',
			$instance->progressCompact( 5, 10 )
		);
	}

	public function testSection() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			"\n--- Foo -------------------------------------------------------------------\n",
			$instance->section( 'Foo' )
		);
	}

	public function testTwoColsOverride() {
		$instance = new CliMsgFormatter();

		$op = "\033[0G";

		$this->assertEquals(
			"{$op}Foo                                                                     Bar",
			$instance->twoColsOverride( 'Foo', 'Bar' )
		);
	}

	public function testTwoLineOverride() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			"\x1b[AFoo\nBar",
			$instance->twoLineOverride( 'Foo', 'Bar' )
		);
	}

	public function testTwoCols() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			"Foo                                                                     Bar\n",
			$instance->twoCols( 'Foo', 'Bar' )
		);
	}

	public function testOneCol() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			"Foo\n",
			$instance->oneCol( 'Foo' )
		);
	}

	public function testGetLen() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			13,
			$instance->getLen( 'Foo', 10 )
		);
	}

	public function testFirstColLen() {
		$instance = new CliMsgFormatter();
		$instance->setFirstColLen( 10 );
		$instance->incrFirstColLen( 5 );

		$this->assertEquals(
			15,
			$instance->getFirstColLen()
		);
	}

	public function testFirstCol() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			'          Foo',
			$instance->firstCol( 'Foo', 10 )
		);
	}

	public function testPositionCol() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			'Foo',
			$instance->positionCol( 'Foo', 0 )
		);

		$this->assertEquals(
			3,
			$instance->getFirstColLen()
		);

		$instance->setFirstColLen( 20 );

		$this->assertEquals(
			'          Foo',
			$instance->positionCol( 'Foo', 30 )
		);

		$this->assertEquals(
			33,
			$instance->getFirstColLen()
		);
	}

	public function testSecondCol() {
		$instance = new CliMsgFormatter();

		$this->assertEquals(
			"                                                                     foobar\n",
			$instance->secondCol( 'foobar' )
		);

		$instance->setFirstColLen( 20 );

		$this->assertEquals(
			"                                                 foobar\n",
			$instance->secondCol( 'foobar' )
		);
	}

}
