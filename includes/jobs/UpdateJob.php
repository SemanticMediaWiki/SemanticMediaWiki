<?php

namespace SMW;

use ParserOutput;
use LinkCache;
use WikiPage;
use Revision;
use Title;
use User;
use Job;

/**
 * UpdateJob is responsible for the asynchronous update of semantic data
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Daniel M. Herzig
 * @author Markus Krötzsch
 * @author mwjames
 */

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
 * @ingroup Job
 */
class UpdateJob extends JobBase {

	/** @var ContentParser */
	protected $contentParser = null;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 */
	function __construct( Title $title ) {
		parent::__construct( 'SMW\UpdateJob', $title );
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	public function run() {
		Profiler::In( __METHOD__ . '-run' );

		LinkCache::singleton()->clear();

		if ( $this->getTitle() === null ) {
			$this->setLastError( __METHOD__ . ': Invalid title' );
			Profiler::Out( __METHOD__ . '-run' );
			return false;
		} elseif ( !$this->getTitle()->exists() ) {
			$this->getStore()->deleteSubject( $this->getTitle() ); // be sure to clear the data
			Profiler::Out( __METHOD__ . '-run' );
			return true;
		}

		if ( !$this->getContentParser()->getOutput() instanceof ParserOutput ) {
			$this->setLastError( $this->getContentParser()->getErrors() );
			return false;
		}

		Profiler::In( __METHOD__ . '-update' );

		$parserData = new ParserData( $this->getTitle(), $this->getContentParser()->getOutput() );
		$parserData->disableUpdateJobs();
		$parserData->updateStore();

		Profiler::Out( __METHOD__ . '-update' );
		Profiler::Out( __METHOD__ . '-run' );

		return true;
	}

	/**
	 * Returns a ContentParser object
	 *
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	protected function getContentParser() {

		if ( $this->contentParser === null ) {
			$this->contentParser = new ContentParser( $this->title );
			$this->contentParser->parse();
		}

		return $this->contentParser;
	}

	/**
	 * This actually files the job. This is prevented if the configuration of SMW
	 * disables jobs.
	 * @note Any method that inserts jobs with Job::batchInsert or otherwise must
	 * implement this check individually. The below is not called in these cases.
	 *
	 * @codeCoverageIgnore
	 */
	function insert() {
		if ( $this->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}
}

/**
 * SMWUpdateJob
 *
 * @deprecated since 1.9
 * @codeCoverageIgnore
 */
class_alias( 'SMW\UpdateJob', 'SMWUpdateJob' );
