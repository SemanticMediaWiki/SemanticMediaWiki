<?php

namespace SMW\Importer;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;

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
	public function doImport() {

		if ( $this->isEnabled === false ) {
			return $this->messageReporter->reportMessage( "\nSkipping the import process.\n" );
		}

		if ( $this->reqVersion === false ) {
			return $this->messageReporter->reportMessage( "\nImport support not enabled, processing completed.\n" );
		}

		$import = false;

		foreach ( $this->contentIterator as $key => $importContents ) {
			$this->messageReporter->reportMessage( "\nImport of $key ...\n" );

			foreach ( $importContents as $impContents ) {

				if ( $impContents->getVersion() !== $this->reqVersion ) {
					$this->messageReporter->reportMessage( "   ... version mismatch, abort import for $key\n" );
					break;
				}

				$this->doImportContents( $impContents );
			}

			$this->messageReporter->reportMessage( "   ... done.\n" );
			$import = true;
		}

		if ( $this->contentIterator->getErrors() !== [] ) {
			$this->messageReporter->reportMessage(
				"\n" . 'Import failed on "' . implode( ", ", $this->contentIterator->getErrors() ) . '"'
			);
		}

		if ( $import ) {
			$this->messageReporter->reportMessage( "\nImport processing completed.\n" );
		}
	}

	private function doImportContents( ImportContents $importContents ) {

		$indent = '   ...';

		if ( $importContents->getErrors() === [] ) {
			$this->contentCreator->setMessageReporter( $this->messageReporter );
			$this->contentCreator->create( $importContents );
		}

		foreach ( $importContents->getErrors() as $error ) {
			$this->messageReporter->reportMessage( "$indent " . $error . " ...\n" );
		}
	}

}
