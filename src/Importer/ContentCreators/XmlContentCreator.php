<?php

namespace SMW\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use SMW\Importer\ContentCreator;
use SMW\Importer\ImportContents;
use SMW\Services\ImporterServiceFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class XmlContentCreator implements ContentCreator {

	/**
	 * @var ImportContentsIterator
	 */
	private $importerServiceFactory;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.0
	 *
	 * @param ImporterServiceFactory $importerServiceFactory
	 */
	public function __construct( ImporterServiceFactory $importerServiceFactory ) {
		$this->importerServiceFactory = $importerServiceFactory;
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
		return $importContents->getContentType() === ImportContents::CONTENT_XML;
	}

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 */
	public function create( ImportContents $importContents ) {

		$indent = '   ...';

		if ( $importContents->getOption( 'skip' ) === true || $importContents->getContentsFile() === '' ) {
			return $this->messageReporter->reportMessage( "\n   " . $importContents->getDescription() . " was skipped.\n" );
		}

		$importSource = $this->importerServiceFactory->newImportStreamSource(
			@fopen( $importContents->getContentsFile(), 'rt' )
		);

		$importer = $this->importerServiceFactory->newWikiImporter(
			$importSource
		);

		$importer->setDebug( false );
		$importer->setPageOutCallback( [ $this, 'reportPage' ] );

		if ( $importContents->getDescription() !== '' ) {
			$this->messageReporter->reportMessage( "\n   " . $importContents->getDescription() . "\n" );
		}

		try {
			$importer->doImport();
		} catch ( \Exception $e ) {
			$this->messageReporter->reportMessage( "Failed with " . $e->getMessage() );
		}

		$this->messageReporter->reportMessage( "$indent done.\n" );
	}

	/**
	 * @see WikiImporter::handlePage
	 *
	 * @param Title $title
	 * @param ForeignTitle $foreignTitle
	 * @param int $revisionCount
	 * @param int $successCount
	 * @param array $pageInfo
	 */
	public function reportPage( $title, $foreignTitle, $revisionCount, $successCount, $pageInfo ) {

		$indent = '   ...';

		// Invalid or non-importable title
		if ( $title === null ) {
			return;
		}

		$title->invalidateCache();

		if ( $successCount > 0 ) {
			$this->messageReporter->reportMessage( "$indent importing " . $title->getPrefixedText() . "\n" );
		} else {
			$this->messageReporter->reportMessage( "$indent skipping " . $title->getPrefixedText() . ", no new revision\n" );
		}
	}

}
