<?php

namespace SMW\Tests\Benchmark;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class CliOutputFormatter {

	const FORMAT_TREE = 'format.tree';
	const FORMAT_JSON = 'format.json';

	/**
	 * @var string
	 */
	private $formatType;

	/**
	 * @since 2.5
	 *
	 * @param string $formatType
	 */
	public function __construct( $formatType = self::FORMAT_TREE ) {
		$this->formatType = $formatType;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $report
	 */
	public function format( array $report  ) {

		if ( $this->formatType === self::FORMAT_TREE ) {
			return $this->doFormatAsTree( $report );
		}

		return json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	}

	private function doFormatAsTree( $report, $label = '', $level = 1 ) {
		$output = '';

		if ( is_string( $label ) && $label !== '' ) {
			$output .= sprintf( "%s- %s\n", str_repeat( ' ', $level ), $label );
			$level++;
		}

		foreach ( $report as $key => $value ) {

			$isDeeper = false;

			if ( is_array( $value ) ) {
				foreach ( $value as $p => $v ) {
					if ( is_array( $v ) ) {
						$isDeeper = true;
					}
				}
			}

			if ( $isDeeper ) {
				$output .= $this->doFormatAsTree( $value, $key, $level + 1 );
			} else {
				$output .= sprintf( "%s- %s: %s\n", str_repeat( ' ', $level ), $key, json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
			}
		}

		return $output;
	}

}
