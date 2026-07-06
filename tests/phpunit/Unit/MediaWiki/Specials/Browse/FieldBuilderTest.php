<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Browse;

use PHPUnit\Framework\TestCase;
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
class FieldBuilderTest extends TestCase {

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
