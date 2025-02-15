<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\ApiModuleManager;

/**
 * @covers \SMW\MediaWiki\Hooks\ApiModuleManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ApiModuleManagerTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ApiModuleManager::class,
			new ApiModuleManager()
		);
	}

	public function testApiModuleManager() {
		$modules = [
			'smwinfo' => '\SMW\MediaWiki\Api\Info',
			'smwtask' => '\SMW\MediaWiki\Api\Task',
			'smwbrowse' => '\SMW\MediaWiki\Api\Browse',
			'ask' => '\SMW\MediaWiki\Api\Ask',
			'askargs' => '\SMW\MediaWiki\Api\AskArgs',
			'browsebysubject' => '\SMW\MediaWiki\Api\BrowseBySubject',
			'browsebyproperty' => '\SMW\MediaWiki\Api\BrowseByProperty'
		];

		$apiModuleManager = $this->getMockBuilder( '\ApiModuleManager' )
			->disableOriginalConstructor()
			->getMock();

		$apiModuleManager->expects( $this->once() )
			->method( 'addModules' )
			 ->with( $modules );

		$instance = new ApiModuleManager();
		$instance->process( $apiModuleManager );
	}

	public function testApiModuleManager_Disabled() {
		$apiModuleManager = $this->getMockBuilder( '\ApiModuleManager' )
			->disableOriginalConstructor()
			->getMock();

		$apiModuleManager->expects( $this->never() )
			->method( 'addModules' );

		$instance = new ApiModuleManager();

		$instance->setOptions(
			[
				'SMW_EXTENSION_LOADED' => false
			]
		);

		$instance->process( $apiModuleManager );
	}

}
