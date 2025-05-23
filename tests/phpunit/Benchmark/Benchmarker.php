<?php

namespace SMW\Tests\Benchmark;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class Benchmarker {

	/**
	 * @var array
	 */
	private $container = [];

	/**
	 * @var bool
	 */
	private $useAsSample = false;

	/**
	 * @var int
	 */
	private $roundFactor;

	/**
	 * @since 2.1
	 */
	public function __construct( $roundFactor = 7 ) {
		$this->roundFactor = $roundFactor;
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->container = [];
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param boalean $state false when takling about the entire population;
	 * true when it is a sample (a selection from a population)
	 */
	public function useAsSample( $state = true ) {
		$this->useAsSample = $state;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param int $roundFactor
	 */
	public function roundBy( $roundFactor ) {
		$this->roundFactor = $roundFactor;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param int $point
	 * @param int $decimals
	 */
	public function addBenchmarkPoint( $point, $decimals = 10 ) {
		$this->container[] = number_format( $point, $decimals );
	}

	/**
	 * @since 2.1
	 *
	 * @param int[] $poins
	 */
	public function addBenchmarkPoints( array $poins ) {
		$this->container = $poins;
	}

	/**
	 * @since 2.1
	 *
	 * @return int
	 */
	public function getSum() {
		return $this->round( array_sum( $this->container ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return int
	 */
	public function getMean() {
		return $this->round( $this->getSum() / count( $this->container ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return int
	 */
	public function getVariance() {
		$mean  = $this->getMean();
		$count = count( $this->container );

		$sumOfSquares = 0;

		foreach ( $this->container as $value ) {
			$sumOfSquares += pow( $value - $mean, 2 );
		}

		return $sumOfSquares / ( $this->useAsSample ? $count - 1 : $count );
	}

	/**
	 * @since 2.1
	 *
	 * @return int
	 */
	public function getStandardDeviation() {
		return $this->round( (float)sqrt( $this->getVariance() ) );
	}

	/**
	 * @since 2.1
	 *
	 * @param int $basis
	 *
	 * @return int
	 */
	public function getStandardScoreBy( $basis = 0 ) {
		return $this->round( ( $basis - $this->getMean() ) / $this->getStandardDeviation() );
	}

	/**
	 * @since 2.1
	 *
	 * @param int $basis
	 *
	 * @return int
	 */
	public function getNormalizedValueBy( $basis = 1 ) {
		return $this->round( $this->getMean() / $basis );
	}

	private function round( $value ) {
		return round( $value, $this->roundFactor );
	}

}
