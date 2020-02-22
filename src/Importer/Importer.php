<?php

namespace SMW\Importer;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Importer implements MessageReporterAware {

	/**
	 * @var ContentIterator
	 */
	private $contentIterator;

	/**
	 * @var ContentCreator
	 */
	private $contentCreator;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @var integer|boolean
	 */
	private $reqVersion = false;

	/**
	 * @since 2.5
	 *
	 * @param ContentIterator $contentIterator
	 * @param ContentCreator $contentCreator
	 */
	public function __construct( ContentIterator $contentIterator, ContentCreator $contentCreator ) {
		$this->contentIterator = $contentIterator;
		$this->contentCreator = $contentCreator;
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isEnabled
	 */
	public function isEnabled( $isEnabled ) {
		$this->isEnabled = $isEnabled;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|boolean $reqVersion
	 */
	public function setReqVersion( $reqVersion ) {
		$this->reqVersion = $reqVersion;
	}

	/**
	 * @since 2.5
	 */
	public function runImport() {

		if ( $this->isEnabled === false ) {
			return $this->messageReporter->reportMessage( "\nImport support was not enabled (or skipped), stopping the task.\n" );
		}

		if ( $this->reqVersion === false ) {
			return $this->messageReporter->reportMessage( "\nRequired import version is missing, stopping the task.\n" );
		}

		foreach ( $this->contentIterator as $key => $contents ) {
			$this->messageReporter->reportMessage( "\nImporting from $key ...\n" );

			foreach ( $contents as $importContents ) {

				if ( $importContents->getVersion() !== $this->reqVersion ) {
					$this->messageReporter->reportMessage( "   ... version mismatch, abort import for $key\n" );
					break;
				}

				$this->doImport( $importContents );
			}

			$this->messageReporter->reportMessage( "   ... done.\n" );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		if ( $this->contentIterator->getErrors() !== [] ) {
			$this->messageReporter->reportMessage(
				"\n" . $cliMsgFormatter->oneCol( 'Import failed on:' ) .
				$cliMsgFormatter->wordWrap( $this->contentIterator->getErrors() )
			);
		}
	}

	private function doImport( ImportContents $importContents ) {

		$cliMsgFormatter = new CliMsgFormatter();

		if ( $importContents->getErrors() === [] ) {
			$this->contentCreator->setMessageReporter( $this->messageReporter );
			$this->contentCreator->create( $importContents );
		}

		$errors = $importContents->getErrors();
		$count = count( $errors );

		foreach ( $errors as $k => $error ) {

			if ( is_array( $error ) ) {
				$error = implode( ', ', $error );
			}

			$prefix = $k == ( $count - 1 ) ? '└' : '├';

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->oneCol( "$prefix $error", 7 )
			);
		}
	}

}
