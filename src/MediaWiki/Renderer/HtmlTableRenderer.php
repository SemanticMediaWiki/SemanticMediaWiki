<?php

namespace SMW\MediaWiki\Renderer;

use Html;

/**
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class HtmlTableRenderer {

	/**
	 * @var array
	 */
	private $headerItems = [];

	/**
	 * @var array
	 */
	private $tableRows = [];

	/**
	 * @var array
	 */
	private $rawRows = [];

	/**
	 * @var array
	 */
	private $tableHeaders = [];

	/**
	 * @var array
	 */
	private $rawHeaders = [];

	/**
	 * @var array
	 */
	private $tableCells = [];

	/**
	 * @var array
	 */
	private $transpose = false;

	/**
	 * @par Example:
	 * @code
	 * $tableBuilder = new TableBuilder();
	 *
	 * $tableBuilder
	 *  	->addHeader( 'Foo' )
	 *  	->addHeader( 'Bar' )
	 *  	->addCell( 'Lula' )
	 *  	->addCell( 'Lala' )
	 *  	->addRow();
	 *
	 * $tableBuilder->getHtml()
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param boolean $htmlContext
	 */
	public function __construct( $htmlContext = false ) {
		$this->htmlContext = $htmlContext;
	}

	/**
	 * @since 2.1
	 *
	 * @param boolean $htmlContext
	 */
	public function setHtmlContext( $htmlContext ) {
		$this->htmlContext = $htmlContext;
		return $this;
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $transpose
	 *
	 * @return TableBuilder
	 */
	public function transpose( $transpose = true ) {
		$this->transpose = $transpose;
		return $this;
	}

	/**
	 * Adds an arbitrary header item to an internal array
	 *
	 * @since 1.9
	 *
	 * @param string $element
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function addHeaderItem( $element, $content = '', $attributes = [] ) {
		$this->headerItems[] = Html::rawElement( $element, $attributes, $content );
	}

	/**
	 * Returns concatenated header items
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHeaderItems() {
		return implode( '', $this->headerItems );
	}

	/**
	 * Collects and adds table cells
	 *
	 * @since 1.9
	 *
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return TableBuilder
	 */
	public function addCell( $content = '', $attributes = [] ) {
		if ( $content !== '' ) {
			$this->tableCells[] = $this->createCell( $content, $attributes );
		}
		return $this;
	}

	/**
	 * Collects and adds table headers
	 *
	 * @since 1.9
	 *
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return TableBuilder
	 */
	public function addHeader( $content = '', $attributes = [] ) {
		if ( $content !== '' ) {
			$this->rawHeaders[] = [ 'content' => $content, 'attributes' => $attributes ];
		}
		return $this;
	}

	/**
	 * Build a row from invoked cells, copy them into a new associated array
	 * and delete those cells as they are now part of a row
	 *
	 * @par Example:
	 * @code
	 *  ...
	 *  $TableBuilder->addCell( 'Lula' )->addCell( 'Lala' )->addRow()
	 *  ...
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param array $attributes
	 *
	 * @return TableBuilder
	 */
	public function addRow( $attributes = [] ) {
		if ( $this->tableCells !== [] ) {
			$this->rawRows[] = [ 'cells' => $this->tableCells, 'attributes' => $attributes ];
			$this->tableCells = [];
		}
		return $this;
	}

	/**
	 * Returns a table
	 *
	 * @since 1.9
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function getHtml( $attributes = [] ) {

		$table = $this->transpose ? $this->buildTransposedTable() : $this->buildStandardTable();

		if ( $this->transpose ) {
			$attributes['data-transpose'] = true;
		}

		if ( $table !== '' ) {
			return Html::rawElement( 'table', $attributes, $table );
		}

		return '';
	}

	private function createRow( $content = '', $attributes = [] ) {
		$alternate = count( $this->tableRows ) % 2 == 0 ? 'row-odd' : 'row-even';

		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = $attributes['class'] . ' ' . $alternate;
		} else {
			$attributes['class'] = $alternate;
		}

		return Html::rawElement( 'tr', $attributes, $content );
	}

	private function createCell( $content = '', $attributes = [] ) {
		return Html::rawElement( 'td', $attributes, $content );
	}

	private function createHeader( $content = '', $attributes = [] ) {
		return Html::rawElement( 'th', $attributes, $content );
	}

	private function doConcatenatedRows() {

		if ( $this->htmlContext ) {
			return Html::rawElement( 'tbody', [], implode( '', $this->tableRows ) );
		}

		return implode( '', $this->tableRows );
	}

	private function buildStandardTable() {
		$this->tableHeaders = [];
		$this->tableRows = [];

		foreach( $this->rawHeaders as $i => $header ) {
			$this->tableHeaders[] = $this->createHeader( $header['content'], $header['attributes'] );
		}

		foreach( $this->rawRows as $row ) {
			$this->tableRows[] = $this->createRow( implode( '', $row['cells'] ), $row['attributes'] );
		}

		return $this->doConcatenatedHeader() . $this->doConcatenatedRows();
	}

	private function doConcatenatedHeader() {
		if ( $this->htmlContext ) {
			return Html::rawElement(
				'thead',
				[],
				$this->getHeaderRowHtml()
			);
		}

		return $this->getHeaderRowHtml();
	}

	private function getHeaderRowHtml() {
		if ( $this->tableHeaders === [] ) {
			return '';
		}

		return Html::rawElement(
			'tr',
			[],
			implode( '', $this->tableHeaders )
		);
	}

	private function buildTransposedTable() {
		$this->tableRows = [];

		foreach( $this->rawHeaders as $hIndex => $header ) {
			$cells = [];
			$headerItem =  $this->createHeader( $header['content'], $header['attributes'] );

			foreach( $this->rawRows as $rIndex => $row ) {
				$cells[] = $this->getTransposedCell( $hIndex, $row );
			}

			// Collect new rows
			$this->tableRows[] = $this->createRow( $headerItem . implode( '', $cells ) );
		}

		return $this->doConcatenatedHeader() . $this->doConcatenatedRows();
	}

	private function getTransposedCell( $index, $row ) {

		if ( isset( $row['cells'][$index] ) ) {
			return $row['cells'][$index];
		}

		$attributes = [];

		if ( isset( $row['attributes']['class'] ) && $row['attributes']['class'] === 'smwfooter' ) {
			$attributes = [ 'class' => 'footer-cell' ];
		}

		return $this->createCell( '', $attributes );
	}

}
