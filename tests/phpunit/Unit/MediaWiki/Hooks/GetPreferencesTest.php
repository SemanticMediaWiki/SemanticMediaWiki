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

		$preferences = [];

		$this->assertInstanceOf(
			GetPreferences::class,
			new GetPreferences( $user )
		);
	}

	/**
	 * @dataProvider keyProvider
	 */
	public function testProcess( $key ) {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$instance = new GetPreferences( $user );

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => false
			]
		);

		$instance->process( $preferences );

		$this->assertArrayHasKey(
			$key,
			$preferences
		);
	}

	public function keyProvider() {

		$provider[] = [
			'smw-prefs-intro'
		];

		$provider[] = [
			'smw-prefs-ask-options-tooltip-display'
		];

		$provider[] = [
			'smw-prefs-general-options-time-correction'
		];

		$provider[] = [
			'smw-prefs-general-options-disable-editpage-info'
		];

		$provider[] = [
			'smw-prefs-general-options-jobqueue-watchlist'
		];

		return $provider;
	}

}
