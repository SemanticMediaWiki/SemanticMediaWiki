<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\FieldMapper;

/**
 * @covers \SMW\Elastic\QueryEngine\FieldMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FieldMapperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FieldMapper::class,
			new FieldMapper()
		);
	}

	public function testIsPhrase() {

		$this->assertTrue(
			FieldMapper::isPhrase( '"Foo bar"' )
		);

		$this->assertFalse(
			FieldMapper::isPhrase( 'Foo"bar' )
		);
	}

	public function testHasWildcard() {

		$this->assertTrue(
			FieldMapper::hasWildcard( 'Foo*' )
		);

		$this->assertFalse(
			FieldMapper::hasWildcard( 'foo\*' )
		);
	}

}
