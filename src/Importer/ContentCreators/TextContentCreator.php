<?php

namespace SMW\Importer\ContentCreators;

use ContentHandler;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Importer\ContentCreator;
use SMW\Importer\ImportContents;
use SMW\MediaWiki\Database;
use SMW\MediaWiki\TitleFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextContentCreator implements ContentCreator {

	use MessageReporterAwareTrait;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @since 2.5
	 *
	 * @param TitleFactory $titleFactory
	 * @param Database $connection
	 */
	public function __construct( TitleFactory $titleFactory, Database $connection ) {
		$this->titleFactory = $titleFactory;
		$this->connection = $connection;
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
	public function create( ImportContents $importContents ) {

		if ( !class_exists( 'ContentHandler' ) ) {
			return $this->messageReporter->reportMessage( "\nContentHandler doesn't exist therefore importing is not possible.\n" );
		}

		$indent = '   ...';
		$indent_e = '      ';
		$name = $importContents->getName();

		if ( $name === '' ) {
			return $this->messageReporter->reportMessage( "$indent no valid page name, abort import." );
		}

		$title = $this->titleFactory->newFromText(
			$name,
			$importContents->getNamespace()
		);

		if ( $title === null ) {
			return $this->messageReporter->reportMessage( "$indent $name returned with a null title, abort import." );
		}

		$page = $this->titleFactory->createPage( $title );
		$prefixedText = $title->getPrefixedText();

		$replaceable = false;

		if ( $importContents->getOption( 'canReplace' ) ) {
			$replaceable = $importContents->getOption( 'canReplace' );
		} elseif( $importContents->getOption( 'replaceable' ) ) {
			$replaceable = $importContents->getOption( 'replaceable' );
		}

		if ( isset( $replaceable['LAST_EDITOR'] ) && $replaceable['LAST_EDITOR'] === 'IS_IMPORTER' ) {
			$replaceable = $this->isCreatorLastEditor( $page );
		}

		if ( $title->exists() && !$replaceable ) {
			return $this->messageReporter->reportMessage( "$indent skipping $prefixedText\n$indent_e already exists ...\n" );
		} elseif( $title->exists() && $replaceable ) {
			$this->messageReporter->reportMessage( "$indent replacing $prefixedText\n$indent_e importer was last editor ...\n" );
		} elseif( $title->exists() ) {
			$this->messageReporter->reportMessage( "$indent replacing $prefixedText ...\n" );
		} else {
			$this->messageReporter->reportMessage( "$indent creating $prefixedText ...\n" );
		}

		// Avoid a possible "Notice: WikiPage::doEditContent: Transaction already
		// in progress (from DatabaseUpdater::doUpdates), performing implicit
		// commit ..."
		$this->connection->onTransactionIdle( function() use ( $page, $title, $importContents ) {
			$this->doCreateContent( $page, $title, $importContents );
		} );
	}

	private function doCreateContent( $page, $title, $importContents ) {

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

	private function isCreatorLastEditor( $page ) {

		$lastEditor = \User::newFromID(
			$page->getUser()
		);

		if ( !$lastEditor instanceof \User ) {
			return false;
		}

		$creator = $page->getCreator();

		if ( !$creator instanceof \User ) {
			return false;
		}

		return $creator->equals( $lastEditor );
	}

}
