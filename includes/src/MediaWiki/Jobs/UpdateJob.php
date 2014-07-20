<?php

namespace SMW\MediaWiki\Jobs;

use SMW\FactboxCache;
use SMW\Profiler;
use SMW\Application;

use ParserOutput;
use LinkCache;
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
 * @ingroup SMW
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
	 * @since  1.9
	 *
	 * @param Title $title
	 */
	function __construct( Title $title ) {
		parent::__construct( 'SMW\UpdateJob', $title );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @return boolean
	 */
	public function run() {
		Profiler::In( __METHOD__ . '-run' );

		LinkCache::singleton()->clear();

		$result = $this->doUpdate();

		Profiler::Out( __METHOD__ . '-run' );
		return $result;
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
		if ( Application::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}

	private function doUpdate() {
		return $this->getTitle()->exists() ? $this->doParseContentForData() : $this->clearData();
	}

	private function clearData() {
		Application::getInstance()->getStore()->deleteSubject( $this->getTitle() );
		return true;
	}

	private function doParseContentForData() {

		$contentParser = Application::getInstance()->newContentParser( $this->getTitle() );
		$contentParser->forceToUseParser();
		$contentParser->parse();

		if ( !( $contentParser->getOutput() instanceof ParserOutput ) ) {
			$this->setLastError( $contentParser->getErrors() );
			return false;
		}

		return $this->updateStore( $contentParser->getOutput() );
	}

	private function updateStore( ParserOutput $parserOutput ) {
		Profiler::In( __METHOD__ . '-update' );

		$cache = Application::getInstance()->getCache();
		$cache->setKey( FactboxCache::newCacheId( $this->getTitle()->getArticleID() ) )->delete();

		// TODO
		// Rebuild the factbox

		$parserData = Application::getInstance()->newParserData(
			$this->getTitle(),
			$parserOutput
		);

		$parserData->disableBackgroundUpdateJobs()->updateStore();

		Profiler::Out( __METHOD__ . '-update' );
		return true;
	}

}
