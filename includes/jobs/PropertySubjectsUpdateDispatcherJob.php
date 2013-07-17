<?php

namespace SMW;

use Title;
use Job;

/**
 * Background dispatch to generate necessary UpdateJob's in order
 * to restore the data parity between a property in its attached subjects
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
 * @author mwjames
 */

/**
 * Background dispatch to generate necessary UpdateJob's in order
 * to restore the data parity between a property in its attached subjects
 *
 * @ingroup Job
 * @ingroup Dispatcher
 */
class PropertySubjectsUpdateDispatcherJob extends Job {

	/** $var Store */
	protected $store = null;

	/** $var Job */
	protected $jobs = array();

	/** $var boolean */
	protected $enabled = true;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 * @param integer $id job id
	 */
	public function __construct( Title $title, $params = array(), $id = 0 ) {
		parent::__construct( 'SMW\PropertySubjectsUpdateDispatcherJob', $title, $params, $id );
		$this->store = StoreFactory::getStore( isset( $params['store'] ) ? $params['store'] : null );
	}

	/**
	 * Sets Store object
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Disables ability to insert jobs into the
	 * JobQueue
	 *
	 * @since 1.9
	 *
	 * @return PropertySubjectsUpdateDispatcherJob
	 */
	public function disable() {
		$this->enabled = false;
		return $this;
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {
		Profiler::In( __METHOD__, true );

		if ( $this->title->getNamespace() === SMW_NS_PROPERTY ) {
			$this->getSubjects( DIProperty::newFromUserLabel( $this->title->getText() ) )->push();
		}

		Profiler::Out( __METHOD__, true );
		return true;
	}

	/**
	 * Insert batch jobs
	 *
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job );
	 *
	 * @since 1.9
	 */
	public function push() {
		$this->enabled ? Job::batchInsert( $this->jobs ) : null;
	}

	/**
	 * Generates list of involved subjects
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 */
	protected function getSubjects( DIProperty $property ) {
		Profiler::In( __METHOD__, true );

		// Array of all subjects that have some value for the given property
		$subjects = $this->store->getAllPropertySubjects( $property );

		$this->addJobs( $subjects );

		// Hook deprecated with 1.9
		wfRunHooks( 'smwUpdatePropertySubjects', array( &$this->jobs ) );

		// Hook since 1.9
		wfRunHooks( 'SMW::Data::UpdatePropertySubjects', array( &$this->jobs ) );

		// Fetch all those that have an error property attached and
		// re-run it through the job-queue
		$subjects = $this->store->getPropertySubjects(
			new DIProperty( DIProperty::TYPE_ERROR ),
			DIWikiPage::newFromTitle( $this->title )
		);

		$this->addJobs( $subjects );

		Profiler::Out( __METHOD__, true );
		return $this;
	}

	/**
	 * Helper method to iterate over an array of DIWikiPage and return and
	 * array of UpdateJobs
	 *
	 * Check whether a job with the same getPrefixedDBkey string (prefixed title,
	 * with underscores and any interwiki and namespace prefixes) is already
	 * registered and if so don't insert a new job. This is particular important
	 * for pages that include a large amount of subobjects where the same Title
	 * and ParserOutput object is used (subobjects are included using the same
	 * WikiPage which means the resulting ParserOutput object is the same)
	 *
	 * @since 1.9
	 *
	 * @param DIWikiPage[] $subjects
	 */
	protected function addJobs( array $subjects = array() ) {

		foreach ( $subjects as $subject ) {

			$duplicate = false;
			$title     = $subject->getTitle();

			if ( $title instanceof Title ) {

				// Avoid duplicates by comparing the title DBkey
				foreach ( $this->jobs as $job ) {
					if ( $job->getTitle()->getPrefixedDBkey() === $title->getPrefixedDBkey() ){
						$duplicate = true;
						break;
					}
				}

				if ( !$duplicate ) {
					$this->jobs[] = new UpdateJob( $title );
				}
			}
		}
	}
}
