<?php

namespace SMW\Importer\ContentCreators;

use Onoi\MessageReporter\MessageReporter;
use SMW\Importer\ContentCreator;
use SMW\Importer\ImportContents;
use SMW\Services\ImporterServiceFactory;
use SMW\Utils\CliMsgFormatter;

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
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @var string
	 */
	private $action = '';

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

		$this->cliMsgFormatter = new CliMsgFormatter();
		$this->action = 'DONE';

		if (
			$importContents->getOption( 'skip' ) === true ||
			$importContents->getContentsFile() === '' ) {
			return $this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoCols( '... ' . $importContents->getDescription() . ' ...', '[SKIP]', 3 )
			);
		}

		$importSource = $this->importerServiceFactory->newImportStreamSource(
			@fopen( $importContents->getContentsFile(), 'rt' )
		);

		$importer = $this->importerServiceFactory->newWikiImporter(
			$importSource
		);

		$importer->setDebug( false );
		$importer->setPageOutCallback( [ $this, 'reportPage' ] );

		$info = pathinfo( $importContents->getContentsFile(), PATHINFO_BASENAME );

		if ( $importContents->getDescription() !== '' ) {
			$info .= " (" . $importContents->getDescription() . ')';
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( "... $info ...", 3 )
		);

		try {
			$importer->doImport();
		} catch ( \Exception $e ) {
			$this->action = 'FAILED';
			$importContents->addError( $e->getMessage() );
		}

		$this->reportAction();
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

		// Invalid or non-importable title
		if ( $title === null ) {
			return;
		}

		$title->invalidateCache();
		$this->reportAction();

		// `IDENTICAL` refers to that no new revision has been created hence
		// the content is identical
		$state = $successCount > 0 ? "IMPORT" : "IDENTICAL,SKIP";

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '... ' . $title->getPrefixedText() . ' ...', "[$state]", 7 )
		);
	}

	private function reportAction() {

		if ( $this->action === '' ) {
			return;
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->secondCol( "[{$this->action}]" )
		);

		$this->action = '';
	}

}
