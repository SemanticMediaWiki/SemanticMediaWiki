<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\FieldBuilder;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\FieldBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FieldBuilderTest extends \PHPUnit\Framework\TestCase {

	public function testGetQueryFormData() {
		$this->assertIsArray(

			FieldBuilder::getQueryFormData( 'Foo' )
		);
	}

	public function testCreateLink() {
		$parameters = [];

		$this->assertIsString(

			FieldBuilder::createLink( 'Foo', $parameters )
		);
	}

}
