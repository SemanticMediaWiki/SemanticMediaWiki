<?php

namespace SMW\Query;

use SMW\DIWikiPage;

/**
 * Record scores for query results retrieved from stores that support the computation
 * of relevance scores.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ScoreSet {

	/**
	 * @var []
	 */
	private $scores = [];

	/**
	 * @var integer|null
	 */
	private $max_score = null;

	/**
	 * @var integer|null
	 */
	private $min_score = null;

	/**
	 * @since 3.0
	 *
	 * @param string|integer $max_score
	 */
	public function max_score( $max_score ) {
		$this->max_score = $max_score;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|integer $min_score
	 */
	public function min_score( $min_score ) {
		$this->min_score = $min_score;
	}

	/**
	 * @note The hash is expected to match DIWikiPage::getHash to easily match
	 * result subjects available in an QueryResult instance.
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage|string $hash
	 * @param string|integer $score
	 */
	public function addScore( $hash, $score, $pos = null ) {

		if ( $hash instanceof DIWikiPage ) {
			$hash = $hash->getHash();
		}

		if ( $pos === null ) {
			$this->scores[] = [ $hash, $score ];
		} else {
			$this->scores[$pos] = [ $hash, $score ];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|string $hash
	 *
	 * @return string|integer|false
	 */
	public function getScore( $hash ) {

		if ( $hash instanceof DIWikiPage ) {
			$hash = $hash->getHash();
		}

		foreach ( $this->scores as $map ) {
			if ( $map[0] === $hash ) {
				return $map[1];
			}
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getScores() {
		return $this->scores;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $usort
	 */
	public function usort( $usort ) {

		if ( !$usort|| $this->scores === [] ) {
			return;
		}

		usort( $this->scores, function( $a, $b ) {

			if ( $a[1] == $b[1] ) {
				return 0;
			}

			return ( $a[1] > $b[1] ) ? -1 : 1;
		} );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public function asTable( $class = '' ) {

		if ( $this->scores === [] ) {
			return '';
		}

		$table = "<table class='$class'><thead>";
		$table .= "<th>Score</th><th>Subject</th><th><span title='Sorting position'>Pos</span></th>";
		$table .= "</thead><tbody>";

		ksort( $this->scores );

		foreach ( $this->scores as $pos => $set ) {
			$table .= '<tr><td>' . $set[1] . '</td><td>' . $set[0] . '</td><td>' . $pos . '</td></tr>';
		}

		$table .= '</tbody></table>';

		return $table;
	}

}
