<?php

namespace SMW;
use Job, Title;

/**
 * Migration job Store2 > Store3
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class MigrationJob extends Job {

	function __construct( $title, $params ) {
		parent::__construct( 'SMWMigrationJob', $title, $params );
	}

	/**
	 * Handles the batch run process
	 *
	 * @return boolean status
	 */
	public function run() {
		// pos == 0 means we start from scratch and have to pack jobs
		if ( $this->params['pos'] == 0 ){
		 $this->packJobs();
		} else {
		 $this->unpackJobs();
		}
		return true;
	}

	/**
	 * Handles the packing process
	 *
	 * @since 1.9
	 */
	private function packJobs(){
		$i = 0;

		// DB instance
		$dbw = wfGetDB( DB_MASTER );
		$oldStore = new \SMWSQLStore2();
		$newStore = new \SMWSQLStore3();
		$res = $dbw->select(
			'smw_ids',
			array(
				'smw_id'
			)
		);

		// Process
		foreach ( $res as $row ) {
			// Collect id's
			$i++;
			$ids[] = $row->smw_id;

			// Pack ids into batches and create a self-reference
			if ( $i % $this->params['b'] == 0 ){
				$title = Title::newFromText( $i );
				Job::factory( 'SMWMigrationJob', $title, array( 'pos' => $i , 'id' => serialize ( $ids ), 'i' => $this->params['i'] ) )->insert();
				$ids = array();
			}
		}

		// Get leftovers that wheren't in the last batch
		if ( $ids !== array() ){
			$title = Title::newFromText( $i );
			Job::factory( 'SMWMigrationJob', $title, array( 'pos' => $i , 'id' => serialize ( $ids ), 'i' => $this->params['i'] ) )->insert();
			$ids = array();
		}
	}

	/**
	 * Handle the unpacking process
	 *
	 * @since 1.9
	 */
	private function unpackJobs(){
		$dbw = wfGetDB( DB_MASTER );
		$oldStore = new \SMWSQLStore2();
		$newStore = new \SMWSQLStore3();

		// Somewhat lazy but it is the simplest way to get
		// back the array of id stored with this batch
		$runningId = unserialize ( $this->params['id'] );

		foreach ( $runningId as $id ) {

			$res = $dbw->select(
				'smw_ids',
				array(
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject'
				),
				array( 'smw_id' => $id )
			);

			foreach ( $res as $row ) {
				// Only for titles that are "real"
				if ( $row->smw_title !== '' && $row->smw_title{0} !== '_' ) {
					$subject = new \SMWDIWikiPage( $row->smw_title, $row->smw_namespace, $row->smw_iw, $row->smw_subobject );
					$newStore->getWriter()->doFlatDataUpdate( $oldStore->getSemanticData( $subject ), $dbw );
				}
			}
		}

		// Sleep interval before to continue
		if ( $this->params['i'] ) {
			sleep( $this->params['i'] );
		}
		wfWaitForSlaves();
	}
}