<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use SMW\ApplicationFactory;

/**
 * RefreshJob iterates over all page ids of the wiki, to perform an update
 * action for all of them in sequence. This corresponds to the in-wiki version
 * of the SMW_refreshData.php script for updating the whole wiki.
 *
 * @note This class ignores $smwgEnableUpdateJobs and always creates updates.
 * In fact, it might be needed specifically on wikis that do not use update
 * jobs in normal operation.
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class RefreshJob extends Job {

	/**
	 * Constructor. The parameters optionally specified in the second
	 * argument of this constructor use the following array keys:
	 *
	 * - 'spos' : (start index, default 1),
	 * - 'prog' : (progress indicator, default 0),
	 * - 'rc' : (number of runs to be done, default 1)
	 *
	 * If more than one run is done, then the first run will restrict to properties
	 * and types. The progress indication refers to the current run, not to the
	 * overall job.
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params = [ 'spos' => 1, 'prog' => 0, 'rc' => 1 ] ) {
		parent::__construct( 'smw.refresh', $title, $params );
	}

	/**
	 * @see Job::run
	 *
	 * @return boolean
	 */
	public function run() {

		if ( $this->hasParameter( 'spos' ) ) {
			$this->refreshData( $this->getParameter( 'spos' ) );
		}

		return true;
	}

	/**
	 * Report the estimated progress status of this job as a number between
	 * 0 and 1 (0% to 100%). The progress refers to the state before
	 * processing this job.
	 *
	 * @return double
	 */
	public function getProgress() {

		$prog = $this->hasParameter( 'prog' ) ? $this->getParameter( 'prog' ) : 0;
		$run  = $this->hasParameter( 'run' ) ? $this->getParameter( 'run' ): 1;
		$rc   = $this->hasParameter( 'rc' ) ? $this->getParameter( 'rc' ) : 1;

		return round( ( $run - 1 + $prog ) / $rc, 1 );
	}

	/**
	 * @param $spos start index
	 */
	protected function refreshData( $spos ) {

		$run  = $this->hasParameter( 'run' ) ? $this->getParameter( 'run' ) : 1;

		$entityRebuildDispatcher = ApplicationFactory::getInstance()->getStore()->refreshData(
			$spos,
			20,
			$this->getNamespace( $run )
		);

		$entityRebuildDispatcher->rebuild( $spos );
		$prog = $entityRebuildDispatcher->getEstimatedProgress();

		if ( $spos > 0 ) {

			$this->createNextJob( [
				'spos' => $spos,
				'prog' => $prog,
				'rc'   => $this->getParameter( 'rc' ),
				'run'  => $run
			] );

		} elseif ( $this->hasParameter( 'rc' ) && $this->getParameter( 'rc' ) > $run ) { // do another run from the beginning

			$this->createNextJob( [
				'spos' => 1,
				'prog' => 0,
				'rc'   => $this->getParameter( 'rc' ),
				'run'  => $run + 1
			] );

		}

		return true;
	}

	protected function createNextJob( array $parameters ) {

		$job = new self(
			$this->getTitle(),
			$parameters
		);

		$job->isEnabledJobQueue( $this->isEnabledJobQueue );
		$job->insert();
	}

	protected function getNamespace( $run ) {

		if ( !$this->hasParameter( 'rc' ) ) {
			return false;
		}

		return ( ( $this->getParameter( 'rc' ) > 1 ) && ( $run == 1 ) ) ? [ SMW_NS_PROPERTY ] : false;
	}

}
