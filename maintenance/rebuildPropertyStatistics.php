<?php

namespace SMW\Maintenance;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the property usage statistics.
 *
 * TODO: make this work with all stores (Right now it only works with SQLStore3)
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RebuildPropertyStatistics extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the property usage statistics (only works with SQLStore3 for now)';

		parent::__construct();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		if ( !defined( 'SMW_VERSION' ) ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$dbw = wfGetDB( DB_MASTER );

		$statsTable = new \SMW\SQLStore\PropertyStatisticsTable(
			\SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
			$dbw
		);

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = new \SMW\ObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$statusRebuilder = new \SMW\SQLStore\SimplePropertyStatisticsRebuilder( $reporter );
		$statusRebuilder->rebuild( $statsTable, $dbw );
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\RebuildPropertyStatistics';
require_once( RUN_MAINTENANCE_IF_MAIN );
