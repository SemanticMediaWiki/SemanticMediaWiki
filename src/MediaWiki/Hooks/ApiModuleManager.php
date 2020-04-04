<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ApiModuleManager implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @since 3.1
	 *
	 * @param ApiModuleManager $apiModuleManager
	 */
	public function process( \ApiModuleManager $apiModuleManager ) {

		if ( $this->getOption( 'SMW_EXTENSION_LOADED' ) === false ) {
			return;
		}

		$modules = [
			'smwinfo' => '\SMW\MediaWiki\Api\Info',
			'smwtask' => '\SMW\MediaWiki\Api\Task',
			'smwbrowse' => '\SMW\MediaWiki\Api\Browse',
			'ask' => '\SMW\MediaWiki\Api\Ask',
			'askargs' => '\SMW\MediaWiki\Api\AskArgs',
			'browsebysubject' => '\SMW\MediaWiki\Api\BrowseBySubject',
			'browsebyproperty' => '\SMW\MediaWiki\Api\BrowseByProperty'
		];

		$apiModuleManager->addModules( $modules, 'action' );
	}

}
