<?php

namespace SMW\Importer;

use Title;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMW\MediaWiki\PageCreator;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ContentsImporter implements MessageReporterAware {

	/**
	 * @var ImportContentsIterator
	 */
	private $importContentsIterator;

	/**
	 * @var PageCreator
	 */
	private $pageCreator;

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
	 * @param ImportContentsIterator $importContentsIterator
	 * @param PageCreator $pageCreator
	 */
	public function __construct( ImportContentsIterator $importContentsIterator, PageCreator $pageCreator ) {
		$this->importContentsIterator = $importContentsIterator;
		$this->pageCreator = $pageCreator;
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

		if ( !class_exists( 'ContentHandler' ) ) {
			return $this->messageReporter->reportMessage( "\nContentHandler doesn't exist therefore importing is not possible.\n" );
		}

		if ( $this->reqVersion === false ) {
			return $this->messageReporter->reportMessage( "\nImport support not enabled, processing completed.\n" );
		}

		foreach ( $this->importContentsIterator as $key => $importContents ) {
			$this->messageReporter->reportMessage( "\nImport of $key ...\n" );

			foreach ( $importContents as $impContents ) {

				if ( $impContents->getVersion() !== $this->reqVersion ) {
					$this->messageReporter->reportMessage( "   ... version mismatch, abort import for $key\n" );
					break;
				}

				$this->doImportContents( $impContents );
			}
		}

		$this->messageReporter->reportMessage( "\nImport processing completed.\n" );
	}

	private function doImportContents( ImportContents $importContents ) {

		$indent = '   ...';

		if ( $importContents->getErrors() !== array() ) {
			return $this->messageReporter->reportMessage( "$indent ... " . implode( ',', $importContents->getErrors() ) ." ...\n" );
		}

		$name = $importContents->getName();

		$title = Title::newFromText(
			$name,
			$importContents->getNamespace()
		);

		if ( $title === null ) {
			return $this->messageReporter->reportMessage( "$indent $name returned with a null instance, abort import." );
		}

		$prefixedText = $title->getPrefixedText();

		if ( $title->exists() && !$importContents->getOption( 'canReplace' ) ) {
			return $this->messageReporter->reportMessage( "$indent skipping $prefixedText, already exists ...\n" );
		} elseif( $title->exists() ) {
			$this->messageReporter->reportMessage( "$indent replacing $prefixedText contents ...\n" );
		} else {
			$this->messageReporter->reportMessage( "$indent creating $prefixedText contents ...\n" );
		}

		$this->doCreateContent( $title, $importContents );
	}

	private function doCreateContent( $title, $importContents ) {

		$page = $this->pageCreator->createPage( $title );

		$content = \ContentHandler::makeContent(
			$importContents->getContents(),
			$title
		);

		$page->doEditContent(
			$content,
			$importContents->getDescription(),
			EDIT_FORCE_BOT
		);

		$title->invalidateCache();
	}

}
