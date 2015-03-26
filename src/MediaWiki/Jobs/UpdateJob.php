<?php

namespace SMW\MediaWiki\Jobs;

use LinkCache;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\Factbox\FactboxCache;
use SMW\SemanticDataCache;
use SMW\DIProperty;
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

		if ( $this->getTitle()->exists() ) {
			return $this->doPrepareForUpdate();
		}

		$this->applicationFactory->getStore()->deleteSubject( $this->getTitle() );

		return true;
	}

	private function doPrepareForUpdate() {
		return $this->needToParsePageContentBeforeUpdate();
	}

	private function needToParsePageContentBeforeUpdate() {

		$contentParser = $this->applicationFactory->newContentParser( $this->getTitle() );
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

		$cache = $this->applicationFactory->getCache();
		$cache->setKey( FactboxCache::newCacheId( $this->getTitle()->getArticleID() ) )->delete();

		// TODO
		// Rebuild the factbox


		// Set a different updateIndentifier to ensure that the updateJob
		// will force a comparison of old/new data during the store update
		$parserData->getSemanticData()->setUpdateIdentifier( 'update-job' );

		$parserData->disableBackgroundUpdateJobs();
		$parserData->updateStore();

		return true;
	}

}
