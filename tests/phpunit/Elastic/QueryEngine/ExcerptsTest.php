<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\Excerpts;

/**
 * @covers \SMW\Elastic\QueryEngine\Excerpts
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ExcerptsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Excerpts::class,
			new Excerpts()
		);
	}

	public function testGetExcerpt_StrippedTagsOnString() {

		$instance = new Excerpts();

		$instance->addExcerpt( 'Foo', '<div style="display:none;">Foo<em>bar</em></div>' );

		$this->assertEquals(
			'Foo<em>bar</em>',
			$instance->getExcerpt( 'Foo' )
		);
	}

	public function testGetExcerpt_StrippedTagsOnArray() {

		$instance = new Excerpts();

		$instance->addExcerpt( 'Bar', [
			'test_field' => [ '<div style="display:none;">Foo<em>bar</em></div>', 'Fooba<em>r</em>' ]
		] );

		$this->assertEquals(
			'Foo<em>bar</em> Fooba<em>r</em>',
			$instance->getExcerpt( 'Bar' )
		);
	}

}
