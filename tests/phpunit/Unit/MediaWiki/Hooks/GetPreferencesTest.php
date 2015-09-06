<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\GetPreferences;

/**
 * @covers \SMW\MediaWiki\Hooks\GetPreferences
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class GetPreferencesTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = array();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\GetPreferences',
			new GetPreferences( $user, $preferences )
		);
	}

	public function testProcess() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = array();

		$instance = new GetPreferences( $user, $preferences );
		$instance->process();

		$this->assertArrayHasKey( 'smw-prefs-intro', $preferences );
		$this->assertArrayHasKey( 'smw-prefs-ask-options-tooltip-display', $preferences );
		$this->assertArrayHasKey( 'smw-prefs-ask-options-collapsed-default', $preferences );
	}

}
