<?php

/**
 * This Command line file resets the property counts in
 * the smw_stats table.
 *
 * @author Nischay Nahata
 * @file
 * @ingroup SMWMaintenance
 */

/**
 * @defgroup SMWMaintenance SMWMaintenance
 * This group contains all parts of SMW that are maintenance scripts.
 * @ingroup SMW
 */

require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: dirname( __FILE__ ) . '/../../../maintenance/Maintenance.php' );

class SMWResetStats extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Resets the Property Stats (counts) for Store3 (Version 1.8). This does not depend on your default backend.';
		$this->addArg( 'limit', 'Reset counts for these many properties starting from offset', false );
		$this->addArg( 'offset', 'Reset counts from this property offset.', false );
	}

	public function execute() {

		$options = array();
		if ( $this->hasArg( 0 ) ) {
			if ( $this->hasArg( 1 ) ) {
				$options['LIMIT'] = $this->getArg( 0 );
				$options['OFFSET'] = $this->getArg( 1 );
			}
		}
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
				'smw_ids',
				array( 'smw_id', 'smw_title', 'smw_sortkey' ),
				array( 'smw_namespace' => SMW_NS_PROPERTY  ),
				__METHOD__,
				$options
		);
		$proptables = SMWSQLStore3::getPropertyTables();

		foreach ( $res as $row ) {
			$di = new SMWDIProperty( $row->smw_title );
			$tableId = SMWSQLStore3::findPropertyTableID( $di );
			$proptable = $proptables[$tableId];
			$propRow = $dbw->selectRow(
					$proptable->name,
					'Count(*) as count',
					$proptable->fixedproperty ? array() : array('p_id' => $row->smw_id ),
					__METHOD__
			);
			echo 'Usage count for '.$row->smw_title.' is '.$propRow->count."\n";
			$dbw->replace(
				'smw_stats',
				'pid',
				array(
					'pid' => $row->smw_id,
					'usage_count' => $propRow->count
				),
				__METHOD__
			);
		}

		$dbw->freeResult( $res );
	}
}

$maintClass = 'SMWResetStats';
require_once( RUN_MAINTENANCE_IF_MAIN );
