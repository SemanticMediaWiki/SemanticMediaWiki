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

	private $hookDispatcher;
	private $permissionExaminer;

	protected function setUp() : void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			GetPreferences::class,
			new GetPreferences( $this->permissionExaminer, $schemaFactory )
		);
	}

	/**
	 * @dataProvider keyProvider
	 */
	public function testProcess( $key ) {

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder->expects( $this->any() )
			->method( 'getSchemaListByType' )
			->will( $this->returnValue( $schemaList ) );

		$this->schemaFactory->expects( $this->any() )
			->method( 'newSchemaFinder' )
			->will( $this->returnValue( $schemaFinder ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$instance = new GetPreferences(
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
