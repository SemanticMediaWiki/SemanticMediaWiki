<?php

namespace SMW\Tests\Listener\ChangeListener;

use SMW\Listener\ChangeListener\ChangeListenerAwareTrait;

/**
 * @covers \SMW\Listener\ChangeListener\ChangeListenerAwareTrait
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ChangeListenerAwareTraitTest extends \PHPUnit\Framework\TestCase {

	public function testRegister_Get_Clear() {
		$changeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListener' )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newChangeListenerAwareClass();

		$instance->registerChangeListener(
			$changeListener
		);

		$this->assertEquals(
			[ spl_object_hash( $changeListener ) => $changeListener ],
			$instance->getChangeListeners()
		);

		$instance->clearChangeListeners();

		$this->assertEquals(
			[],
			$instance->getChangeListeners()
		);
	}

	private function newChangeListenerAwareClass() {
		return new class() {

			use ChangeListenerAwareTrait;

		};
	}

}
