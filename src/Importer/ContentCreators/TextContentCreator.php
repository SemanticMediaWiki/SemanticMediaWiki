<?php

namespace SMW\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use SMW\MediaWiki\PageCreator;
use SMW\Importer\ImportContents;
use SMW\Importer\ContentCreator;
use SMW\MediaWiki\Database;
use ContentHandler;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextContentCreator implements ContentCreator {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var PageCreator
	 */
	private $pageCreator;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @since 2.5
	 *
	 * @param PageCreator $pageCreator
	 * @param Database $connection
	 */
	public function __construct( PageCreator $pageCreator, Database $connection ) {
		$this->pageCreator = $pageCreator;
		$this->connection = $connection;
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
	 * @param ImportContents $importContents
	 */
	public function canCreateContentsFor( ImportContents $importContents ) {
		return $importContents->getContentType() === ImportContents::CONTENT_TEXT;
	}

	/**
	 * @since 2.5
	 *
	 * @param ImportContents $importContents
	 */
	public function doCreateFrom( ImportContents $importContents ) {

		if ( !class_exists( 'ContentHandler' ) ) {
			return $this->messageReporter->reportMessage( "\nContentHandler doesn't exist therefore importing is not possible.\n" );
		}

		$indent = '   ...';
		$name = $importContents->getName();

		if ( $name === '' ) {
			return $this->messageReporter->reportMessage( "$indent no valid page name, abort import." );
		}

		$title = Title::newFromText(
			$name,
			$importContents->getNamespace()
		);

		if ( $title === null ) {
			return $this->messageReporter->reportMessage( "$indent $name returned with a null title, abort import." );
		}

		$prefixedText = $title->getPrefixedText();

		if ( $title->exists() && !$importContents->getOption( 'canReplace' ) && !$importContents->getOption( 'replaceable' ) ) {
			return $this->messageReporter->reportMessage( "$indent skipping $prefixedText, already exists ...\n" );
		} elseif( $title->exists() ) {
			$this->messageReporter->reportMessage( "$indent replacing $prefixedText contents ...\n" );
		} else {
			$this->messageReporter->reportMessage( "$indent creating $prefixedText contents ...\n" );
		}

		// Avoid a possible "Notice: WikiPage::doEditContent: Transaction already
		// in progress (from DatabaseUpdater::doUpdates), performing implicit
		// commit ..."
		$this->connection->onTransactionIdle( function() use ( $title, $importContents ) {
			$this->doCreateContent( $title, $importContents );
		} );
	}

	private function doCreateContent( $title, $importContents ) {

		$page = $this->pageCreator->createPage( $title );

		$content = ContentHandler::makeContent(
			$this->fetchContents( $importContents ),
			$title
		);

		$page->doEditContent(
			$content,
			$importContents->getDescription(),
			EDIT_FORCE_BOT
		);

		$title->invalidateCache();
	}

	private function fetchContents( $importContents ) {

		if ( $importContents->getContentsFile() === '' ) {
			return $importContents->getContents();
		}

		$contents = file_get_contents( $importContents->getContentsFile() );

		// http://php.net/manual/en/function.file-get-contents.php
		return mb_convert_encoding(
			$contents,
			'UTF-8',
			mb_detect_encoding(
				$contents,
				'UTF-8, ISO-8859-1, ISO-8859-2',
				true
			)
		);
	}

}
