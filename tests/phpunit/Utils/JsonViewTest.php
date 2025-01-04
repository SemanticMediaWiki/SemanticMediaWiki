<?php

namespace SMW\Tests\Utils;

use SMW\Utils\JsonView;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\JsonView
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JsonViewTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JsonView::class,
			new JsonView()
		);
	}

	public function testCreate() {
		$messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new JsonView();

		$instance->setMessageLocalizer(
			$messageLocalizer
		);

		$this->assertIsString(

			$instance->create( 'foo', 'bar' )
		);
	}

}
