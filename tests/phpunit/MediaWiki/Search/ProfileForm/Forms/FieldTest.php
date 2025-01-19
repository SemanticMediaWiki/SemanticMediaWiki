<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\Field;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\Field
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FieldTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testTooltip() {
		$instance = new Field();

		$this->assertContains(
			'Foo ... bar',
			$instance->tooltip( [ 'tooltip' => 'Foo ... bar' ] )
		);
	}

	public function testCreate() {
		$instance = new Field();

		$this->assertContains(
			'smw-input-field',
			$instance->create( 'input', [] )
		);

		$this->assertContains(
			'smw-select',
			$instance->create( 'select', [] )
		);
	}

	public function testSelect() {
		$instance = new Field();

		$attr = [
			'list' => [
				'A' => 'Foo',
				'B' => [ 42, 'disabled' ]
			],
			'name' => 'Foobar'
		];

		$html = $instance->select( $attr );

		$this->assertContains(
			'<select name="Foobar">',
			$html
		);

		$this->assertContains(
			"<option value='A'>Foo</option>",
			$html
		);

		$this->assertContains(
			"<option value='B' disabled>42</option>",
			$html
		);
	}

	/**
	 * @dataProvider inputAttributeProvider
	 */
	public function testInput( $attr, $expected ) {
		$instance = new Field();

		$actual = $instance->input( $attr );
		// MW 1.39-1.40 produces self-closing tag, which is invalid HTML
		$actual = str_replace( '/>', '>', $actual );

		$this->assertContains(
			$expected,
			$actual
		);
	}

	public function inputAttributeProvider() {
		yield [
			[ 'name' => 'Foobar' ],
			'<div class="smw-input-field"><input name="Foobar" placeholder=""></div>'
		];

		yield [
			[ 'name' => 'Foobar', 'multifield' => true ],
			'<div class="smw-input-field"><input name="Foobar[]" placeholder=""></div>'
		];
	}

}
