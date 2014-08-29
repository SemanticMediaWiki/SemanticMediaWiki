<?php

namespace SMW\Tests\Benchmark;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Benchmarker {

	/**
	 * @var array
	 */
	private $container = array();

	/**
	 * @var boolean
	 */
	private $useAsSample = false;

	/**
	 * @var integer
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
		$this->container = array();
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
	 * @param integer $roundFactor
	 */
	public function roundBy( $roundFactor ) {
		$this->roundFactor = $roundFactor;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $point
	 * @param integer $decimals
	 */
	public function addBenchmarkPoint( $point, $decimals = 10 ) {
		$this->container[] = number_format( $point, $decimals );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer[] $poins
	 */
	public function addBenchmarkPoints( array $poins ) {
		$this->container = $poins;
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
	 */
	public function getSum() {
		return $this->round( array_sum( $this->container ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
	 */
	public function getMean() {
		return $this->round( $this->getSum() / count( $this->container ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
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
	 * @return integer
	 */
	public function getStandardDeviation() {
		return $this->round( (float)sqrt( $this->getVariance() ) );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $basis
	 *
	 * @return integer
	 */
	public function getStandardScoreBy( $basis = 0 ) {
		return $this->round( ( $basis - $this->getMean() ) / $this->getStandardDeviation() );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $basis
	 *
	 * @return integer
	 */
	public function getNormalizedValueBy( $basis = 1 ) {
		return $this->round( $this->getMean() / $basis );
	}

	private function round( $value ) {
		return round( $value, $this->roundFactor );
	}

}
