<?php

namespace SMW\MediaWiki\Hooks;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class HookHandler implements LoggerAwareInterface {

	/**
	 * @var DataItemFactory
	 */
	protected $dataItemFactory;

	/**
	 * @var DataValueFactory
	 */
	protected $dataValueFactory;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @since  2.5
	 *
	 * @param ApplicationFactory|null $applicationFactory
	 */
	public function __construct( $applicationFactory = null ) {

		if ( $applicationFactory === null ) {
			$applicationFactory = ApplicationFactory::getInstance();
		}

		$this->dataItemFactory = $applicationFactory->getDataItemFactory();
		$this->dataValueFactory = $applicationFactory->getDataValueFactory();
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	protected function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
