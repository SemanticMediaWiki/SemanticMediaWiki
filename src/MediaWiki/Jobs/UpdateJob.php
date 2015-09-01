<?php

namespace SMW\MediaWiki\Jobs;

use LinkCache;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\DIProperty;
use SMW\DIWikiPage;
use Title;

/**
 * UpdateJob is responsible for the asynchronous update of semantic data
 * using MediaWiki's JobQueue infrastructure.
 *
 * Update jobs are created if, when saving an article,
 * it is detected that the content of other pages must be re-parsed as well (e.g.
 * due to some type change).
 *
 * @note This job does not update the page display or parser cache, so in general
 * it might happen that part of the wiki page still displays based on old data (e.g.
 * formatting in-page values based on a datatype thathas since been changed), whereas
 * the Factbox and query/browsing interfaces might already show the updated records.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Daniel M. Herzig
 * @author Markus Krötzsch
 * @author mwjames
 */
class UpdateJob extends JobBase {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params
	 */
	function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\UpdateJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @return boolean
	 */
	public function run() {
		return $this->doUpdate();
	}

	/**
	 * @see Job::insert
	 *
	 * This actually files the job. This is prevented if the configuration of SMW
	 * disables jobs.
	 *
	 * @note Any method that inserts jobs with Job::batchInsert or otherwise must
	 * implement this check individually. The below is not called in these cases.
	 *
	 * @codeCoverageIgnore
	 */
	public function insert() {
		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}

	private function doUpdate() {

		LinkCache::singleton()->clear();

		$this->applicationFactory = ApplicationFactory::getInstance();

		if ( $this->matchWikiPageLastModifiedToRevisionLastModified( $this->getTitle() ) ) {
			return true;
		}

		if ( $this->getTitle()->exists() ) {
			return $this->doPrepareForUpdate();
		}

		$this->applicationFactory->getStore()->clearData(
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		return true;
	}

	private function matchWikiPageLastModifiedToRevisionLastModified( $title ) {

		if ( $this->getParameter( 'pm' ) !== ( $this->getParameter( 'pm' ) | SMW_UJ_PM_CLASTMDATE ) ) {
			return false;
		}

		$lastModified = $this->applicationFactory->getStore()->getWikiPageLastModifiedTimestamp(
			DIWikiPage::newFromTitle( $title )
		);

		if ( $lastModified === \WikiPage::factory( $title )->getTimestamp() ) {
			$pageUpdater = $this->applicationFactory->newMwCollaboratorFactory()->newPageUpdater();
			$pageUpdater->addPage( $title );
			$pageUpdater->doPurgeParserCache();
			return true;
		}

		return false;
	}

	private function doPrepareForUpdate() {
		return $this->needToParsePageContentBeforeUpdate();
	}

	/**
	 * SMW_UJ_PM_NP = new Parser to avoid "Parser state cleared" exception
	 */
	private function needToParsePageContentBeforeUpdate() {

		$contentParser = $this->applicationFactory->newContentParser( $this->getTitle() );

		if ( $this->getParameter( 'pm' ) === ( $this->getParameter( 'pm' ) | SMW_UJ_PM_NP ) ) {
			$contentParser->setParser(
				new \Parser( $GLOBALS['wgParserConf'] )
			);
		}

		$contentParser->forceToUseParser();
		$contentParser->parse();

		if ( !( $contentParser->getOutput() instanceof ParserOutput ) ) {
			$this->setLastError( $contentParser->getErrors() );
			return false;
		}

		$parserData = $this->applicationFactory->newParserData(
			$this->getTitle(),
			$contentParser->getOutput()
		);

		return $this->updateStore( $parserData );
	}

	private function updateStore( $parserData ) {

		$dispatchContext = EventHandler::getInstance()->newDispatchContext();
		$dispatchContext->set( 'title', $this->getTitle() );

		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'factbox.cache.delete',
			$dispatchContext
		);

		// TODO
		// Rebuild the factbox

		$parserData->disableBackgroundUpdateJobs();
		$parserData->updateStore();

		return true;
	}

}
