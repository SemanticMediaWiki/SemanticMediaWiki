<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\Schema\SchemaFactory;

/**
 * @covers \SMW\MediaWiki\Hooks\GetPreferences
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class GetPreferencesTest extends TestCase {

	private $hookDispatcher;
	private $permissionExaminer;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$user = $this->getMockBuilder( User::class )
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

		$user = $this->getMockBuilder( User::class )
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
