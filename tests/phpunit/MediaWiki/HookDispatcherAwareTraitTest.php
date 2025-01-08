<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\HookDispatcherAwareTrait;

/**
 * @covers \SMW\MediaWiki\HookDispatcherAwareTrait
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class HookDispatcherAwareTraitTest extends \PHPUnit\Framework\TestCase {

	public function testSetHookDispatcher() {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$hookDispatcher->expects( $this->once() )
			->method( 'onGetPreferences' );

		$instance = $this->newHookDispatcherAware();

		$instance->setHookDispatcher(
			$hookDispatcher
		);

		$instance->callOnGetPreferences( $user, [] );
	}

	private function newHookDispatcherAware() {
		return new class() {

			use HookDispatcherAwareTrait;

			public function callOnGetPreferences( $user, $parameters ) {
				$this->hookDispatcher->onGetPreferences( $user, $parameters );
			}
		};
	}

}
