<?php

namespace SMW\SQLStore\Installer;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SetupFile;
use SMW\Utils\CliMsgFormatter;
use SMW\Setup;
use RuntimeException;
use Wikimedia\Rdbms\IDatabase;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class VersionExaminer {

	use MessageReporterAwareTrait;

	/**
	 * @var IDatabase
	 */
	private $connection;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 3.2
	 *
	 * @param IDatabase $connection
	 */
	public function __construct( $connection ) {
		if ( !$connection instanceof IDatabase ) {
			throw new RuntimeException( "Invalid connection instance!" );
		}

		$this->connection = $connection;
	}

	/**
	 * @since 3.1
	 *
	 * @param SetupFile $setupFile
	 */
	public function setSetupFile( SetupFile $setupFile ) {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $minRequirements
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function defineDatabaseRequirements( array $minRequirements ): array {
		$type = $this->connection->getType();

		if ( !isset( $minRequirements[$type] ) ) {
			throw new RuntimeException(
				"The `$type` was not defined as part of the database minimum requirements!"
			);
		}

		return [
			'type' => $type,
			'latest_version' => $this->connection->getServerInfo(),
			'minimum_version' => $minRequirements[$type]
		];
	}

	/**
	 * @since 3.2
	 *
	 * @param array $minRequirements
	 *
	 * @return bool
	 */
	public function meetsVersionMinRequirement( array $minRequirements ): bool {
		$this->messageReporter->reportMessage( "\nChecking version requirement ..." );
		$this->messageReporter->reportMessage( "\n   ... done.\n" );

		try {
			$requirements = $this->defineDatabaseRequirements( $minRequirements );
		} catch ( RuntimeException $e ) {
			return $this->throwFalseAndNotice( $e->getMessage() );
		}

		if ( !version_compare( $requirements['latest_version'], $requirements['minimum_version'], 'ge' ) ) {
			return $this->throwFalseAndNotice( $requirements );
		}

		$this->setupFile->remove( SetupFile::DB_REQUIREMENTS );

		return true;
	}

	private function throwFalseAndNotice( $requirements = [] ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Compatibility notice' )
		);

		if ( isset( $requirements['type'] ) ) {
			$text = [
				"The `{$requirements['type']}` database version of {$requirements['latest_version']}",
				"doesn't meet the minimum requirement of {$requirements['minimum_version']}",
				"for Semantic MediaWiki.",
				"\n\n",
				"The installation of Semantic MediaWiki was aborted!"
			];
		} else {
			$text = [
				$requirements,
				"\n\n",
				"The installation of Semantic MediaWiki was aborted!"
			];
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->setupFile->set( [ SetupFile::DB_REQUIREMENTS => $requirements ] );
		$this->setupFile->finalize();

		return false;
	}

}
