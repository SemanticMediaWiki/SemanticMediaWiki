<?php

namespace SMW\Tests\Utils\Runners;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RunnerFactory {

	/**
	 * @var RunnerFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.1
	 *
	 * @return RunnerFactory
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $maintenanceClass
	 *
	 * @return MaintenanceRunner
	 */
	public function newMaintenanceRunner( $maintenanceClass ) {
		switch ( $maintenanceClass ) {
			case 'rebuildPropertyStatistics':
				$maintenanceClass = '\SMW\Maintenance\rebuildPropertyStatistics';
				break;
			case 'rebuildData':
				$maintenanceClass = '\SMW\Maintenance\rebuildData';
				break;
			case 'rebuildConceptCache':
				$maintenanceClass = '\SMW\Maintenance\rebuildConceptCache';
				break;
			case 'setupStore':
				$maintenanceClass = '\SMW\Maintenance\setupStore';
				break;
		}

		return new MaintenanceRunner( $maintenanceClass );
	}

	/**
	 * @since 2.1
	 *
	 * @param string|null $jobType
	 *
	 * @return JobQueueRunner
	 */
	public function newJobQueueRunner( $jobType = null ) {
		return new JobQueueRunner( $jobType );
	}

	/**
	 * @since 2.1
	 *
	 * @param string|null $source
	 *
	 * @return XmlImportRunner
	 */
	public function newXmlImportRunner( $source = null ) {
		return new XmlImportRunner( $source );
	}

}
