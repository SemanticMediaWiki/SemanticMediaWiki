<?php

namespace SMW\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use SMW\Importer\ImportContents;
use SMW\Importer\ContentCreator;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DispatchingContentCreator implements ContentCreator {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var ContentCreator[]
	 */
	private $contentCreators = array();

	/**
	 * @since 3.0
	 *
	 * @param ContentCreator[]
	 */
	public function __construct( array $contentCreators ) {
		$this->contentCreators = $contentCreators;
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 3.0
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 */
	public function canCreateContentsFor( ImportContents $importContents ) {

		foreach ( $this->contentCreators as $contentCreator ) {
			if ( $contentCreator->canCreateContentsFor( $importContents ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 * @throws RuntimeException
	 */
	public function doCreateFrom( ImportContents $importContents ) {

		foreach ( $this->contentCreators as $contentCreator ) {
			if ( $contentCreator->canCreateContentsFor( $importContents ) ) {
				$contentCreator->setMessageReporter( $this->messageReporter );
				return $contentCreator->doCreateFrom( $importContents );
			}
		}

		throw new RuntimeException( "No dispatchable ContentsCreator is assigned to type " . $importContents->getContentType() );
	}

}
