<?php

namespace SMW\Tests;

use SemanticMediaWiki;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \SemanticMediaWiki
 * @group semantic-mediawiki
 *
 * @license GNU GPL v3+
 * @since 4.0
 *
 * @author hexmode
 */
class ClassAliasTest extends TestCase {

	protected function setUp() : void {
		parent::setUp();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	/**
	 * @dataProvider classAliasProvider
	 */
	public function testIsAlias( string $alias, string $class ) {
		$newClass = new ReflectionClass( $alias );
		$this->assertEquals( $newClass->name, $class );
	}

	public function classAliasProvider() {
		foreach ( SemanticMediaWiki::getClassAliasMap() as $alias => $class ) {
			yield [ $alias, $class ];
		}
	}
}
