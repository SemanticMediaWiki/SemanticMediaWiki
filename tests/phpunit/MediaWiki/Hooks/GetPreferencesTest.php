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
class GetPreferencesTest extends \PHPUnit\Framework\TestCase {

	private $hookDispatcher;
	private $permissionExaminer;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$this->assertInstanceOf(
			GetPreferences::class,
			new GetPreferences( $this->permissionExaminer, $this->schemaFactory )
		);
	}

	/**
	 * @dataProvider keyProvider
	 */
	public function testProcess( $key ) {
		$this->permissionExaminer->expects( $this->any() )
			->method( 'hasPermissionOf' )
			->willReturn( true );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$instance = new GetPreferences(
			$this->permissionExaminer,
			$this->schemaFactory
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->setOptions(
			[
				'smwgEnabledEditPageHelp' => false
			]
		);

		$instance->process( $user, $preferences );

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
