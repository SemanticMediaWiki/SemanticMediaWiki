<?php

namespace SMW\Importer;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use RuntimeException;

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

		if ( $this->reqVersion === false ) {
			return $this->messageReporter->reportMessage( "\nImport support not enabled, processing completed.\n" );
		}

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
		}

		if ( $this->contentIterator->getErrors() !== array() ) {
			$this->messageReporter->reportMessage(
				"\n" . 'Import failed due to "' . implode( ", ", $this->contentIterator->getErrors() ) . '"'
			);
		}

		$this->messageReporter->reportMessage( "\nImport processing completed.\n" );
	}

	private function doImportContents( ImportContents $importContents ) {

		$indent = '   ...';

		if ( $importContents->getErrors() !== array() ) {
			return $this->messageReporter->reportMessage( "$indent ... " . implode( ',', $importContents->getErrors() ) ." ...\n" );
		}

		$this->contentCreator->setMessageReporter( $this->messageReporter );
		$this->contentCreator->doCreateFrom( $importContents );
	}

}
