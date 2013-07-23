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
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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

	/** @var WikiPage */
	protected $wikiPage = null;

	/** @var Revision */
	protected $revision = null;

	/** @var ParserOutput */
	protected $parserOutput = null;

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

		if ( !$this->getParserOutput() instanceof ParserOutput ) {
			return false;
		}

		Profiler::In( __METHOD__ . '-update' );

		$parserData = new ParserData( $this->getTitle(), $this->getParserOutput() );
		$parserData->disableUpdateJobs();
		$parserData->updateStore();

		Profiler::Out( __METHOD__ . '-update' );
		Profiler::Out( __METHOD__ . '-run' );

		return true;
	}

	/**
	 * Builds and returns a ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput|null
	 */
	public function getParserOutput() {

		if ( $this->parserOutput === null ) {
			Profiler::In( __METHOD__ );

			if ( $this->wikiPage === null ) {
				$this->wikiPage = WikiPage::factory( $this->getTitle() );
			}

			if ( $this->revision === null && $this->wikiPage !== null ) {
				$this->revision = $this->wikiPage->getRevision();
			}

			if ( !$this->revision || $this->revision === null ) {
				$this->setLastError( __METHOD__ . " No revision available for {$this->getTitle()->getPrefixedDBkey()}" );
				Profiler::Out( __METHOD__ );
				return false;
			}

			$this->parserOutput = $this->wikiPage->getParserOutput(
				$this->wikiPage->makeParserOptions( User::newFromId( $this->revision->getUser() ) ),
				$this->revision->getId()
			);

			Profiler::Out( __METHOD__ );
		}

		return $this->parserOutput;
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
