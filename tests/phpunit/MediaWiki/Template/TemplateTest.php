<?php

namespace SMW\Tests\MediaWiki\Template;

use SMW\MediaWiki\Template\Template;

/**
 * @covers \SMW\MediaWiki\Template\Template
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class TemplateTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Template::class,
			 new Template( 'Foo' )
		);
	}

	public function testField() {
		$instance = new Template( 'Foo' );
		$instance->field( 'Bar', 123 );
		$instance->field( 'Bar', 'Foobar' );

		$this->assertSame(
			'{{Foo|Bar=Foobar}}',
			$instance->text()
		);
	}

}
