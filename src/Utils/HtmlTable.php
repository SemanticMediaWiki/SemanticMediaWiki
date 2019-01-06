<?php

namespace SMW\Utils;

use Html;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTable {

	/**
	 * @var array
	 */
	private $headers = [];

	/**
	 * @var array
	 */
	private $cells = [];

	/**
	 * @var array
	 */
	private $rows = [];

	/**
	 * @since 3.0
	 *
	 * @param string $content
	 * @param array $attributes
	 */
	public function header( $content = '', $attributes = [] ) {
		if ( $content !== '' ) {
			$this->headers[] = [ 'content' => $content, 'attributes' => $attributes ];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $content
	 * @param array $attributes
	 */
	public function cell( $content = '', $attributes = [] ) {
		if ( $content !== '' ) {
			$this->cells[] = Html::rawElement( 'td', $attributes, $content );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return TableBuilder
	 */
	public function row( $attributes = [] ) {
		if ( $this->cells !== [] ) {
			$this->rows[] = [ 'cells' => $this->cells, 'attributes' => $attributes ];
			$this->cells = [];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function table( $attributes = [], $transpose = false, $htmlContext = false ) {

		$table = $this->buildTable( $transpose, $htmlContext );

		if ( $transpose ) {
			$attributes['data-transpose'] = true;
		}

		$this->headers = [];
		$this->rows = [];
		$this->cells = [];

		if ( $table !== '' ) {
			return Html::rawElement( 'table', $attributes, $table );
		}

		return '';
	}

	private function buildTable( $transpose, $htmlContext ) {

		if ( $transpose ) {
			return $this->transpose( $htmlContext );
		}

		$headers = [];
		$rows = [];

		foreach( $this->headers as $i => $header ) {
			$headers[] = Html::rawElement( 'th', $header['attributes'], $header['content'] );
		}

		foreach( $this->rows as $row ) {
			$rows[] = $this->createRow( implode( '', $row['cells'] ), $row['attributes'], count( $rows ) );
		}

		return $this->concatenateHeaders( $headers, $htmlContext ) . $this->concatenateRows( $rows, $htmlContext );
	}

	private function transpose( $htmlContext ) {

		$rows = [];

		foreach( $this->headers as $hIndex => $header ) {
			$cells = [];
			$headerItem = Html::rawElement( 'th', $header['attributes'], $header['content'] );

			foreach( $this->rows as $rIndex => $row ) {
				$cells[] = $this->getTransposedCell( $hIndex, $row );
			}

			// Collect new rows
			$rows[] = $this->createRow( $headerItem . implode( '', $cells ), [], count( $rows ) );
		}

		return $this->concatenateRows( $rows, $htmlContext );
	}

	private function createRow( $content = '', $attributes = [], $count ) {

		$alternate = $count % 2 == 0 ? 'row-odd' : 'row-even';

		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = $attributes['class'] . ' ' . $alternate;
		} else {
			$attributes['class'] = $alternate;
		}

		return Html::rawElement( 'tr', $attributes, $content );
	}

	private function concatenateHeaders( $headers, $htmlContext ) {

		if ( $htmlContext ) {
			return Html::rawElement( 'thead', [], implode( '', $headers ) );
		}

		return implode( '', $headers );
	}

	private function concatenateRows( $rows, $htmlContext ) {

		if ( $htmlContext ) {
			return Html::rawElement( 'tbody', [], implode( '', $rows ) );
		}

		return implode( '', $rows );
	}

	private function getTransposedCell( $index, $row ) {

		if ( isset( $row['cells'][$index] ) ) {
			return $row['cells'][$index];
		}

		$attributes = [];

		if ( isset( $row['attributes']['class'] ) && $row['attributes']['class'] === 'smwfooter' ) {
			$attributes = [ 'class' => 'footer-cell' ];
		}

		return Html::rawElement( 'td', $attributes, '' );
	}

}
