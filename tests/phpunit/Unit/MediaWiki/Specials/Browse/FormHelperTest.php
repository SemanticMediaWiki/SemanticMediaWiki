<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\FormHelper;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\FormHelper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FormHelperTest extends \PHPUnit_Framework_TestCase {

	public function testGetQueryForm() {

		$this->assertInternalType(
			'string',
			FormHelper::getQueryForm( 'Foo' )
		);
	}

	public function testCreateLink() {

		$parameters = array();

		$this->assertInternalType(
			'string',
			FormHelper::createLinkFromMessage( 'Foo', $parameters )
		);
	}

}
