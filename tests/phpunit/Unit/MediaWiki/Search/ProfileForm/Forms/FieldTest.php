<?php

namespace SMW\Tests\MediaWiki\Search\ProfileForm\Forms;

use SMW\MediaWiki\Search\ProfileForm\Forms\Field;

/**
 * @covers \SMW\MediaWiki\Search\ProfileForm\Forms\Field
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FieldTest extends \PHPUnit_Framework_TestCase {

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

		$this->assertContains(
			$expected,
			$instance->input( $attr )
		);
	}

	public function inputAttributeProvider() {

		yield [
			[ 'name' => 'Foobar' ],
			'<div class="smw-input-field"><input name="Foobar" placeholder=""/></div>'
		];

		yield [
			[ 'name' => 'Foobar', 'multifield' => true ],
			'<div class="smw-input-field"><input name="Foobar[]" placeholder=""/></div>'
		];
	}

}
