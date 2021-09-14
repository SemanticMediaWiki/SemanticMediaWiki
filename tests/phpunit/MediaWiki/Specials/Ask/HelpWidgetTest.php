<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\HelpWidget;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\HelpWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HelpWidgetTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testSessionFailure() {

		$this->assertContains(
			'ask-help',
			HelpWidget::html()
		);
	}

}
