<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\FormatterWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\FormatterWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormatterWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testDiv() {

		$this->assertInternalType(
			'string',
			FormatterWidget::div( 'foo', [ 'id' => 'bar' ] )
		);
	}


}
