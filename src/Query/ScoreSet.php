<?php

namespace SMW\Query;

use SMW\DataItems\WikiPage;

/**
 * Record scores for query results retrieved from stores that support the computation
 * of relevance scores.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ScoreSet {

	/**
	 * @var
	 */
	private array $scores = [];

	/**
	 * @var int|null
	 */
	private $max_score = null;

	/**
	 * @var int|null
	 */
	private $min_score = null;

	/**
	 * @since 3.0
	 *
	 * @param string|int $max_score
	 */
	public function max_score( $max_score ): void {
		$this->max_score = $max_score;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|int $min_score
	 */
	public function min_score( $min_score ): void {
		$this->min_score = $min_score;
	}

	/**
	 * @note The hash is expected to match WikiPage::getHash to easily match
	 * result subjects available in an QueryResult instance.
	 *
	 * @since 3.0
	 *
	 * @param WikiPage|string $hash
	 * @param string|int $score
	 */
	public function addScore( $hash, $score, $pos = null ): void {
		if ( $hash instanceof WikiPage ) {
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
	 * @param WikiPage|string $hash
	 *
	 * @return string|int|false
	 */
	public function getScore( $hash ) {
		if ( $hash instanceof WikiPage ) {
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
	 * @return
	 */
	public function getScores(): array {
		return $this->scores;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $usort
	 */
	public function usort( $usort ): void {
		if ( !$usort || $this->scores === [] ) {
			return;
		}

		usort( $this->scores, static function ( array $a, array $b ): int {
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
	public function asTable( $class = '' ): string {
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
