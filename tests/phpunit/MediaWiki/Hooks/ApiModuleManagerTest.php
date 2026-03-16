<?php

namespace SMW\Tests\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Ask;
use SMW\MediaWiki\Api\AskArgs;
use SMW\MediaWiki\Api\Browse;
use SMW\MediaWiki\Api\BrowseByProperty;
use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\MediaWiki\Api\Info;
use SMW\MediaWiki\Api\Task;
use SMW\MediaWiki\Hooks\ApiModuleManager;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Hooks\ApiModuleManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ApiModuleManagerTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( Store::class )
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
			'smwinfo' => Info::class,
			'smwtask' => Task::class,
			'smwbrowse' => Browse::class,
			'ask' => Ask::class,
			'askargs' => AskArgs::class,
			'browsebysubject' => BrowseBySubject::class,
			'browsebyproperty' => BrowseByProperty::class
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
