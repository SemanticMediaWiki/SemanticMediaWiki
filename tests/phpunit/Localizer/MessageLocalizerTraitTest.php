<?php

namespace SMW\Tests\Localizer;

use SMW\Localizer\MessageLocalizerTrait;

/**
 * @covers \SMW\Localizer\MessageLocalizerTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class MessageLocalizerTraitTest extends \PHPUnit_Framework_TestCase {

	public function testMsg() {

		$messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$messageLocalizer->expects( $this->once() )
			->method( 'msg' )
			->with(
				$this->equalTo( 'foo' ),
				$this->equalTo( 'bar' ),
				$this->equalTo( 42 ) );

		$instance = $this->newMessageLocalizerClass();

		$instance->setMessageLocalizer(
			$messageLocalizer
		);

		$instance->msg( 'foo', 'bar', 42 );
	}

	private function newMessageLocalizerClass() {
		return new class() {

			use MessageLocalizerTrait;

		};
	}

}
