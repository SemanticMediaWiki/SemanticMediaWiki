<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Store;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use User;

/**
 * @license GNU GPL v2+
 * @since  3.2
 *
 * @author mwjames
 */
class TaskHandlerRegistry {

	use HookDispatcherAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var []
	 */
	private $taskHandlers = [];

	/**
	 * @var int
	 */
	private $featureSet = 0;

	/**
	 * @var bool
	 */
	private $onRegisterTaskHandlers = false;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Store $store, OutputFormatter $outputFormatter ) {
		$this->store = $store;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $taskHandlers
	 * @param User $user
	 */
	public function registerTaskHandlers( array $taskHandlers, User $user ) {

		if ( $this->onRegisterTaskHandlers ) {
			return;
		}

		$this->onRegisterTaskHandlers = true;
		$this->taskHandlers = $taskHandlers;

		$this->hookDispatcher->onRegisterTaskHandlers( $this, $this->store, $this->outputFormatter, $user );
	}

	/**
	 * @since 3.2
	 *
	 * @param TaskHandler $taskHandler
	 */
	public function registerTaskHandler( TaskHandler $taskHandler ) {
		$this->taskHandlers[] = $taskHandler;
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $featureSet
	 */
	public function setFeatureSet( $featureSet ) {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.2
	 *
	 * @return string $type;
	 *
	 * @return TaskHandler[]|[]
	 */
	public function get( string $type ) : array {
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
