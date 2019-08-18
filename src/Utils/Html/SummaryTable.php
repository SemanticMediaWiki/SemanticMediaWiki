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
	 * @var integer
	 */
	private $columnThreshold = 4;

	/**
	 * @var string
	 */
	private $thumbImage = '';

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
	 * @param integer $ColumnThreshold
	 */
	public function setColumnThreshold( $columnThreshold ) {
		$this->columnThreshold = $columnThreshold;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $thumbImage
	 */
	public function noImage() {
		$this->thumbImage = Html::rawElement(
			'div',
			[
				'class' => "smw-summarytable-item-center"
			],
			Html::rawElement(
				'div',
				[
					'class' => "smw-summarytable-noimage"
				]
			)
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param string $thumbImage
	 */
	public function setThumbImage( $thumbImage ) {
		$this->thumbImage = $thumbImage;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function buildHTML( array $opts = [] ) {

		$html = '';
		$tables = [];

		if ( $this->thumbImage !== '' ) {
			return $this->tableAndImage( $this->parameters );
		}

		$count = count( $this->parameters );

		if ( !isset( $opts['columns'] ) || $count < $this->columnThreshold ) {
			return $this->table( $this->parameters );
		}

		$size = round( $count / $opts['columns'] );

		if ( $this->thumbImage !== '' ) {
			$chunks[] = $this->parameters;
			$chunks[] = [ '' => $this->thumbImage ];
		} else {
			$chunks = array_chunk( $this->parameters, $size, true );
		}

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

	private function tableAndImage( $params ) {

		$html = Html::rawElement(
			'div',
			[
				'class' => "smw-summarytable-facts"
			],
			$this->table( $params )
		);

		$html .= Html::rawElement(
			'div',
			[
				'class' => "smw-summarytable-image"
			],
			$this->thumbImage
		);

		return Html::rawElement(
			'div',
			[
				'class' => "smw-summarytable-imagecolumn"
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
