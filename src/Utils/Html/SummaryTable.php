<?php

namespace SMW\Utils\Html;

use Html;
use SMW\Utils\HtmlDivTable;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SummaryTable {

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @var []
	 */
	private $attributes = [];

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 */
	public function __construct( array $parameters = [] ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public static function getModuleStyles() {
		return [ 'ext.smw.style', 'smw.summarytable' ];
	}

	/**
	 * @since 3.1
	 *
	 * @param array $attributes
	 */
	public function setAttributes( array $attributes = [] ) {
		$this->attributes = $attributes;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function buildHTML( array $opts = [] ) {

		$html = '';
		$tables = [];

		$count = count( $this->parameters );

		if ( !isset( $opts['columns'] ) || $count < 4 ) {
			return $this->table( $this->parameters );
		}

		$size = round( $count / $opts['columns'] );

		$chunks = array_chunk( $this->parameters, $size, true );

		foreach ( $chunks as $params ) {
			$tables[] = $this->table( $params );
		}

		$class = 'columns-' . $opts['columns'];

		foreach ( $tables as $table ) {
			$html .= Html::rawElement(
				'div',
				[
					'class' => "smw-summarytable-$class"
				],
				$table
			);
		}

		return Html::rawElement(
			'div',
			[
				'class' => "smw-summarytable-columns"
			],
			$html
		);
	}

	private function table( $params ) {

		$rows = '';
		$html = '';

		foreach ( $params as $key => $value ) {

			if ( $value === '' || $value === null ) {
				continue;
			}

			$attr = [];

			if ( isset( $this->attributes[$key] ) ) {
				$attr = $this->attributes[$key];
			}

			$row = HtmlDivTable::cell( $key, [ 'class' => 'smwpropname' ] );
			$row .= HtmlDivTable::cell( $value, [ 'class' => 'smwprops' ] );
			$rows .= HtmlDivTable::row( $row, $attr );
		}

		$html .= HtmlDivTable::table(
			$rows,
			[
				'class' => 'smwfacttable'
			]
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-summarytable'
			],
			$html
		);
	}

}
