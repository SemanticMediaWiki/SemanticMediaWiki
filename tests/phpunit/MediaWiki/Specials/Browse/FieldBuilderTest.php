<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\FieldBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\FieldBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FieldBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGetQueryFormData() {
		$this->assertInternalType(
			'array',
			FieldBuilder::getQueryFormData( 'Foo' )
		);
	}

	public function testCreateLink() {
		$parameters = [];

		$this->assertInternalType(
			'string',
			FieldBuilder::createLink( 'Foo', $parameters )
		);
	}

}
