<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceHelper {

	/**
	 * @var array
	 */
	private $globals = [];

	/**
	 * @var array
	 */
	private $runtime = [
		'start'  => 0,
		'memory' => 0
	];

	/**
	 * @since 2.2
	 */
	public function initRuntimeValues() {
		$this->runtime['start'] = microtime( true );
		$this->runtime['memory'] = memory_get_peak_usage( false );
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getRuntimeValues() {

		$memory = memory_get_peak_usage( false );
		$time = microtime( true ) - $this->runtime['start'];

		$hTime = round( $time, 2 ) . ' sec';
		$hTime .= ( $time > 60 ? ' (' . round( $time / 60, 2 ) . ' min)' : '' );

		return [
			'time' => $time,
			'humanreadable-time' => $hTime,
			'memory-before' => $this->runtime['memory'],
			'memory-after'  => $memory,
			'memory-used'   => $memory - $this->runtime['memory']
		];
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getFormattedRuntimeValues( $indent = '' ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$output = '';

		foreach ( $this->getRuntimeValues() as $key => $value ) {

			if ( $key === 'time' ) {
				continue;
			}

			if ( $key === 'humanreadable-time' ) {
				$key = 'Time (used)';
			}

			if ( strpos( $key, 'memory-' ) !== false ) {
				$key = str_replace( 'memory-', 'Memory (', $key ) . ')';
			}

			$output .= $cliMsgFormatter->twoCols( "- $key", $value, 0, '.' );
		}

		return $output;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setGlobalToValue( $key, $value ) {

		if ( !isset( $GLOBALS[$key] ) ) {
			return;
		}

		$this->globals[$key] = $GLOBALS[$key];
		$GLOBALS[$key] = $value;
		ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
	}

	/**
	 * @since 2.2
	 */
	public function reset() {

		foreach ( $this->globals as $key => $value ) {
			$GLOBALS[$key] = $value;
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$this->runtime['start'] = 0;
		$this->runtime['memory'] = 0;
	}

}
