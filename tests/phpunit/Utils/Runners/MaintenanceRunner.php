<?php

namespace SMW\Tests\Utils\Runners;

use DomainException;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use RuntimeException;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * Running maintenance scripts via phpunit is not really possible but instead
 * this class allows to execute script related classes that are equivalent to:
 * `php rebuildData.php --< myOptions >`
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class MaintenanceRunner {

	use MessageReporterAwareTrait;

	private ?HookContainer $hookContainer = null;

	protected $output = null;
	protected $quiet = false;

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @since 1.9.2
	 */
	public function __construct(
		protected $maintenanceClass,
		protected $options = [],
	) {
	}

	/**
	 * @since 1.9.2
	 *
	 * @param array $options
	 *
	 * @return MaintenanceRunner
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return MaintenanceRunner
	 */
	public function setQuiet() {
		$this->options['quiet'] = true;
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return bool
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	public function run() {
		if ( !class_exists( $this->maintenanceClass ) ) {
			throw new RuntimeException( "Expected a valid {$this->maintenanceClass} class" );
		}

		$obLevel = ob_get_level();

		// Avoid outdated reference to invoked store instance
		ApplicationFactory::getInstance()->clear();
		$maintenance = new $this->maintenanceClass;

		if ( !( $maintenance instanceof Maintenance ) ) {
			throw new DomainException( "Expected a Maintenance instance" );
		}

		// From MediaWiki 1.46, fatalError() exits the test run instead of
		// throwing unless the script is marked as under test
		if ( property_exists( Maintenance::class, 'isTesting' ) ) {
			TestingAccessWrapper::newFromObject( $maintenance )->isTesting = true;
		}

		// isset/ null
		if ( isset( $this->options['quiet'] ) && $this->options['quiet'] === false ) {
			unset( $this->options['quiet'] );
		}

		$maintenance->loadParamsAndArgs(
			$this->maintenanceClass,
			$this->options
		);

		if ( $this->messageReporter !== null && method_exists( $maintenance, 'setMessageReporter' ) ) {
			$maintenance->setMessageReporter( $this->messageReporter );
		}

		if ( $this->hookContainer !== null && method_exists( $maintenance, 'setHookContainer' ) ) {
			$maintenance->setHookContainer( $this->hookContainer );
		}

		ob_start();

		try {
			$result = $maintenance->execute();
		} finally {
			$this->output = ob_get_contents();

			while ( ob_get_level() > $obLevel ) {
				ob_end_clean();
			}
		}

		return $result;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return string
	 */
	public function getOutput() {
		return $this->output;
	}

}
