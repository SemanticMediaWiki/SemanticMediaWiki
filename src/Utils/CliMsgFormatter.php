<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CliMsgFormatter {

	const OK = '✓';
	const FAILED = '✗';

	/**
	 * Max length of the CLI display
	 */
	const MAX_LEN = 75;

	/**
	 * @var integer|null
	 */
	private $firstColLen = null;

	/**
	 * @var float|int
	 */
	private $startTime = 0;

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function head() : string {

		$head = [
			sprintf( "%-" . ( self::MAX_LEN - mb_strlen( SMW_VERSION ) ) . "s%s\n", "Semantic MediaWiki:", SMW_VERSION ),
			sprintf( "%-" . ( self::MAX_LEN - mb_strlen( MW_VERSION ) ) . "s%s\n", "MediaWiki:", MW_VERSION )
		];

		return implode( "", $head );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $lines
	 *
	 * @return string
	 */
	public function wordwrap( array $lines = [] ) : string {
		return wordwrap( str_replace( "\n ", "\n", implode( " ", $lines ) ), self::MAX_LEN, "\n" );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function red( string $text ) : string {
		return "\x1b[91m$text\x1b[0m";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function green( string $text ) : string {
		return "\x1b[32m$text\x1b[0m";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function yellow( string $text ) : string {
		return "\x1b[33m$text\x1b[0m";
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $i
	 * @param integer $total
	 *
	 * @return string
	 */
	public function progress( int $i, int $total ) : string {
		return min( 100, round( ( $i / $total ) * 100 ) ) . " %";
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $i
	 * @param integer $total
	 * @param integer|null $current
	 * @param integer|null $last
	 * @param integer|null $remainingTime
	 *
	 * @return string
	 */
	public function progressCompact( int $i, int $total, ?int $current = null, ?int $last = null, ?int $remainingTime = null ) : string {

		if ( $current === null ) {
			$current = $i;
		}

		if ( $last === null ) {
			$last = $total;
		}

		$progress = min( 100, round( ( $i / $total ) * 100 ) );

		if ( $remainingTime === null ) {
			return sprintf( "%s / %s (%3.0f%%)", $current, $last, $progress );
		}

		return sprintf( "(%s/%s) [%s] %4.0f%%", $current, $last, $remainingTime, $progress );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $startTime
	 */
	public function setStartTime( int $startTime ) {
		$this->startTime = $startTime;
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $i
	 * @param integer $total
	 *
	 * @return string
	 */
	public function remainingTime( int $i, int $total ) : string {
		return $this->humanReadableTime( ( ( microtime( true ) - $this->startTime ) / $i ) * ( $total - $i ) );
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function elapsedTime() : string {
		return $this->humanReadableTime( ( microtime( true ) - $this->startTime ) );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $title
	 * @param int $indentLen
	 * @param string $placeHolder
	 * @param bool $reverse
	 *
	 * @return string
	 */
	public function section( string $title, int $indentLen = 3, string $placeHolder = '-', bool $reverse = false ) : string {

		if ( $reverse ) {
			$title = "$title" . ( $indentLen == 0 ? '' : ' ' ) . sprintf( "%'{$placeHolder}" . $indentLen . "s", '' );
			return "\n" . sprintf( "%'{$placeHolder}" . ( self::MAX_LEN - mb_strlen( $title ) ) . "s%s", ' ', $title ) . "\n";
		}

		if ( $indentLen > 0 && mb_strlen( $title ) > 0 ) {
			$title = sprintf( "%'{$placeHolder}{$indentLen}s%s", '', ' ' . $title ) . ' ';
		}

		return "\n" . "$title" . sprintf( "%'{$placeHolder}" . ( self::MAX_LEN - mb_strlen( $title ) ) . "s", '' ) . "\n";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $firstCol
	 * @param string $secondCol
	 * @param integer $indentLen
	 * @param string $placeHolder
	 *
	 * @return string
	 */
	public function twoColsOverride( string $firstCol, string $secondCol, int $indentLen = 0, string $placeHolder = ' ' ) : string {

		if ( $placeHolder !== ' ' ) {
			$firstCol = "$firstCol ";
		}

		if ( $indentLen > 0 ) {
			$firstCol = sprintf( "%-{$indentLen}s%s", '', $firstCol );
		}

		$len = self::MAX_LEN - mb_strlen( $secondCol );
		$placeholderLen = $len - mb_strlen( $firstCol );

		// As per https://stackoverflow.com/questions/2124195/command-line-progress-bar-in-php
		// "...osx uses \r as carriage return and line feed ..." hence using
		// `\033[0G` instead

		return ( version_compare( PHP_VERSION, '7.3', '<' ) ? "\r" : "\033[0G" ) . ( sprintf( "%-{$len}s%s", "$firstCol" . sprintf( "%'{$placeHolder}{$placeholderLen}s", ' ' ), $secondCol ) );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $firstLine
	 * @param string $secondLine
	 *
	 * @return string
	 */
	public function twoLineOverride( string $firstLine, string $secondLine ) : string {
		return "\x1b[A" . $firstLine . "\n" . $secondLine;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $firstCol
	 * @param string $secondCol
	 * @param integer $indentLen
	 * @param string $placeHolder
	 *
	 * @return string
	 */
	public function twoCols( string $firstCol, string $secondCol, int $indentLen = 0, string $placeHolder = ' ' ) : string {

		if ( $placeHolder !== ' ' ) {
			$firstCol = "$firstCol ";
		}

		if ( $indentLen > 0 ) {
			$firstCol = sprintf( "%-{$indentLen}s%s", '', $firstCol );
		}

		$len = self::MAX_LEN - mb_strlen( $secondCol );
		$placeholderLen = $len - mb_strlen( $firstCol );

		if ( $placeholderLen <= 0 ) {
			$placeholderLen = 0;
		}

		$content = (
			sprintf( "%-{$len}s%s", "$firstCol" . sprintf( "%'{$placeHolder}{$placeholderLen}s", ' ' ), $secondCol )
		);

		return $this->trimContent( $content ) . "\n";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $value
	 * @param integer $seconds
	 */
	public function countDown( string $message, int $seconds ) {

		if ( $seconds < 1 ) {
			return;
		}

		echo "\n";

		for ( $i = $seconds; $i >= 0; $i-- ) {
			if ( $i != $seconds ) {
				echo str_repeat( "\x08", strlen( $i + 1 ) );
			}

			echo $this->twoColsOverride( $message, "$i s" );

			if ( $i ) {
				sleep( 1 );
			}
		}

		return "\n";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $oneCol
	 * @param integer $indentLen
	 *
	 * @return string
	 */
	public function oneCol( string $oneCol, int $indentLen = 0 ) : string {

		if ( $indentLen > 0 ) {
			$oneCol = sprintf( "%-{$indentLen}s%s", '', $oneCol );
		}

		return $this->trimContent( $oneCol ) . "\n";
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param integer $indentLen
	 *
	 * @return integer
	 */
	public function getLen( string $key, int $indentLen = 0 ) : int {

		if ( $indentLen > 0 ) {
			$key = sprintf( "%-{$indentLen}s%s", '', $key );
		}

		return mb_strlen( $this->trimContent( $key ) );
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $firstColLen
	 */
	public function setFirstColLen( int $firstColLen ) {
		$this->firstColLen = $firstColLen;
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $len
	 */
	public function incrFirstColLen( int $len ) {
		$this->firstColLen += $len;
	}

	/**
	 * @since 3.2
	 *
	 * @return integer|null
	 */
	public function getFirstColLen() : ?int {
		return $this->firstColLen;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $firstCol
	 * @param integer $indentLen
	 * @param integer $expectedSecondColLen
	 *
	 * @return string
	 */
	public function firstCol( string $firstCol, int $indentLen = 0, int $expectedSecondColLen = 0 ) : string {

		if ( $indentLen > 0 ) {
			$firstCol = sprintf( "%-{$indentLen}s%s", '', $firstCol );
		}

		$maxLen = self::MAX_LEN;

		if ( $expectedSecondColLen > 0 ) {
			$maxLen = $maxLen - $expectedSecondColLen;
		}

		$firstCol = $this->trimContent( $firstCol, $maxLen );
		$this->firstColLen = mb_strlen( $firstCol );

		return $firstCol;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $value
	 * @param int $position
	 * @param string $placeHolder
	 *
	 * @return string
	 */
	public function positionCol( string $value, int $position = 0, string $placeHolder = ' ' ) : string {

		if ( $position == 0 ) {
			$len = $position;
			$this->firstColLen += mb_strlen( $value );
			$space = '';
		} else {
			$len = $position - $this->firstColLen;
			$this->firstColLen = $position + mb_strlen( $value );
			$space = ' ';
		}

		return sprintf( "%'{$placeHolder}{$len}s%s", $space, $value );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $value
	 * @param string $placeHolder
	 *
	 * @return string
	 */
	public function secondCol( string $value, string $placeHolder = ' ' ) : string {
		$len = self::MAX_LEN - mb_strlen( $value ) - $this->firstColLen;
		$this->firstColLen = null;

		return sprintf( "%'{$placeHolder}{$len}s%s", ' ', $value ) . "\n";
	}

	private function trimContent( $content, $maxLen = self::MAX_LEN ) {

		$length = mb_strlen( $content ) - 1;
		$startOff = ( $maxLen / 2 ) - 3;
		$endOff = ( $maxLen / 2 ) - 3;

		if ( $length >= $maxLen ) {
			$content = mb_substr( $content, 0, $startOff ) . ' ... ' . mb_substr( $content, $length - $endOff );
		}

		return $content;
	}

	private function humanReadableTime( $time ) {

		$time = round( $time, 2 );

		$s = $time % 60;
		$m = floor( ( $time % 3600 ) / 60 );
		$h = floor( ( $time % 86400 ) / 3600 );

		if ( $h > 0 ) {
			$time = "$h h, $m min";
		} elseif ( $h == 0 && $m > 0 ) {
			$time = "$m min";
		} elseif ( $h == 0 && $m == 0 ) {
			$time = "$s sec";
		}

		return $time;
	}

}
