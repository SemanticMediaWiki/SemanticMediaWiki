<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Api\ApiModuleManager as mwApiModuleManager;
use SMW\MediaWiki\Api\Ask;
use SMW\MediaWiki\Api\AskArgs;
use SMW\MediaWiki\Api\Browse;
use SMW\MediaWiki\Api\BrowseByProperty;
use SMW\MediaWiki\Api\BrowseBySubject;
use SMW\MediaWiki\Api\Info;
use SMW\MediaWiki\Api\Task;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ApiModuleManager implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @since 3.1
	 *
	 * @param mwApiModuleManager $apiModuleManager
	 */
	public function process( mwApiModuleManager $apiModuleManager ): void {
		if ( $this->getOption( 'SMW_EXTENSION_LOADED' ) === false ) {
			return;
		}

		$modules = [
			'smwinfo' => Info::class,
			'smwtask' => Task::class,
			'smwbrowse' => Browse::class,
			'ask' => Ask::class,
			'askargs' => AskArgs::class,
			'browsebysubject' => BrowseBySubject::class,
			'browsebyproperty' => BrowseByProperty::class
		];

		$apiModuleManager->addModules( $modules, 'action' );
	}

}
