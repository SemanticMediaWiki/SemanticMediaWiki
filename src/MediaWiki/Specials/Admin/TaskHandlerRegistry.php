<?php

namespace SMW\MediaWiki\Specials\Admin;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\User;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since  3.2
 *
 * @author mwjames
 */
class TaskHandlerRegistry {

	private ?HookContainer $hookContainer = null;

	private array $taskHandlers = [];

	private int $featureSet = 0;

	private bool $onRegisterTaskHandlers = false;

	/**
	 * @since 3.2
	 */
	public function __construct(
		private Store $store,
		private OutputFormatter $outputFormatter,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @since 3.2
	 */
	public function registerTaskHandlers( array $taskHandlers, User $user ): void {
		if ( $this->onRegisterTaskHandlers ) {
			return;
		}

		$this->onRegisterTaskHandlers = true;
		$this->taskHandlers = $taskHandlers;

		$this->hookContainer->run(
			'SMW::Admin::RegisterTaskHandlers',
			[ $this, $this->store, $this->outputFormatter, $user ]
		);
	}

	/**
	 * @since 3.2
	 */
	public function registerTaskHandler( TaskHandler $taskHandler ): void {
		$this->taskHandlers[] = $taskHandler;
	}

	/**
	 * @since 3.2
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.2
	 */
	public function get( string $type ): array {
		$taskHandlers = [];

		foreach ( $this->taskHandlers as $taskHandler ) {

			$taskHandler->setFeatureSet(
				$this->featureSet
			);

			$taskHandler->setStore(
				$this->store
			);

			if (
				$type === TaskHandler::SECTION_MAINTENANCE &&
				$type === $taskHandler->getSection() ) {
				$taskHandlers[] = $taskHandler;
			}

			if (
				$type === TaskHandler::SECTION_ALERTS &&
				$type === $taskHandler->getSection() ) {
				$taskHandlers[] = $taskHandler;
			}

			if (
				$type === TaskHandler::SECTION_SUPPLEMENT &&
				$type === $taskHandler->getSection() ) {
				$taskHandlers[] = $taskHandler;
			}

			if (
				$type === TaskHandler::SECTION_SUPPORT &&
				$type === $taskHandler->getSection() ) {
				$taskHandlers[] = $taskHandler;
			}

			if (
				$type === TaskHandler::ACTIONABLE &&
				$taskHandler instanceof ActionableTask ) {
				$taskHandlers[] = $taskHandler;
			}
		}

		return $taskHandlers;
	}

}
