<?php

namespace SMW\Elastic;

use SMW\Elastic\Indexer\Rebuilder\Rollover;
use SMW\Elastic\Connection\Client as ElasticClient;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use SMW\SetupFile;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Installer {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;

	/**
	 * @var Rollover
	 */
	private $rollover;

	/**
	 * @since 3.2
	 *
	 * @param Rollover $rollover
	 */
	public function __construct( Rollover $rollover ) {
		$this->rollover = $rollover;
	}

	/**
	 * @since 3.2
	 *
	 * @return SetupFile
	 */
	public function newSetupFile() : SetupFile {
		return new SetupFile();
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function setup() : array {
		return [
			$this->rollover->update( ElasticClient::TYPE_DATA ),
			$this->rollover->update( ElasticClient::TYPE_LOOKUP )
		];
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function drop() : array {
		return [
			$this->rollover->delete( ElasticClient::TYPE_DATA ),
			$this->rollover->delete( ElasticClient::TYPE_LOOKUP )
		];
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 * @param string $version
	 *
	 * @return string
	 */
	public function rollover( $type, $version ) : string {
		return $this->rollover->rollover( $type, $version );
	}

}
