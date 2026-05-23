<?php

namespace SMW\Elastic\Hooks;

use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\ElasticFactory;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;
use SMW\Store;

/**
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Admin::RegisterTaskHandlers
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class RegisterTaskHandlers {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly ElasticFactory $elasticFactory,
	) {
	}

	/**
	 * MediaWiki derives this method name from the hook
	 * `SMW::Admin::RegisterTaskHandlers` when the handler is dispatched via
	 * the declarative `HookHandlers` registration in `extension.json`.
	 *
	 * @since 7.0.0
	 */
	public function onSMW__Admin__RegisterTaskHandlers(
		TaskHandlerRegistry $taskHandlerRegistry,
		Store $store,
		$outputFormatter,
		$user
	): bool {
		return $this->onRegisterTaskHandlers( $taskHandlerRegistry, $store, $outputFormatter, $user );
	}

	/**
	 * @since 7.0.0
	 */
	public function onRegisterTaskHandlers(
		TaskHandlerRegistry $taskHandlerRegistry,
		Store $store,
		$outputFormatter,
		$user
	): bool {
		$connection = $store->getConnection( 'elastic' );
		if ( $connection === null || $connection instanceof DummyClient ) {
			return true;
		}

		$taskHandler = $this->elasticFactory->newInfoTaskHandler(
			$store,
			$outputFormatter
		);

		$taskHandlerRegistry->registerTaskHandler( $taskHandler );

		return true;
	}

}
