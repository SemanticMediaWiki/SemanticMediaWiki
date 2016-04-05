<?php

namespace SMW\Maintenance;

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
	private $globals = array();

	/**
	 * @var array
	 */
	private $runtime = array(
		'start'  => 0,
		'memory' => 0
	);

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

		return array(
			'time' => microtime( true ) - $this->runtime['start'],
			'memory-before' => $this->runtime['memory'],
			'memory-after'  => $memory,
			'memory-used'   => $memory - $this->runtime['memory']
		);
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function transformRuntimeValuesForOutput() {

		$runtimeValues = $this->getRuntimeValues();

		$time = round( $runtimeValues['time'], 2 ) . ' sec ';
		$time .= ( $runtimeValues['time'] > 60 ? '(' . round( $runtimeValues['time'] / 60, 2 ) . ' min)' : '' );

		return "Memory used: " . $runtimeValues['memory-used'] . " (" .
			"b: " . $runtimeValues['memory-before'] . ", ".
			"a: " . $runtimeValues['memory-after'] . ") with a " .
			"runtime of " . $time;
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
	}

	/**
	 * @since 2.2
	 */
	public function reset() {

		foreach ( $this->globals as $key => $value ) {
			$GLOBALS[$key] = $value;
		}

		$this->runtime['start'] = 0;
		$this->runtime['memory'] = 0;
	}

}
