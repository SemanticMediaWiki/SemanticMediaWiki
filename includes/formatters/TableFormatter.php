<?php

namespace SMW;

use Html;

/**
 * Class handling Html table formatting
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class handling Html table formatting
 *
 * @ingroup Formatter
 */
class TableFormatter {

	/** @var array */
	protected $headerItems = array();

	/** @var array */
	protected $tableRows = array();
	protected $rawRows = array();

	/** @var array */
	protected $tableHeaders = array();
	protected $rawHeaders = array();

	/** @var array */
	protected $tableCells = array();

	/** @var boolean */
	protected $transpose = false;

	/**
	 * @par Example:
	 * @code
	 *  $tableFormatter = new SMW\TableFormatter();
	 *
	 *  // Setup the header
	 *  $tableFormatter->addTableHeader( 'Foo' )
	 *  $tableFormatter->addTableHeader( 'Bar' )
	 *
	 *  // Add row
	 *  $tableFormatter->addTableCell( 'Lula' )
	 *  $tableFormatter->addTableCell( 'Lala' )
	 *  $tableFormatter->addTableRow()
	 *  ...
	 *
	 *  // Get table
	 *  $tableFormatter->getTable() // Standard table
	 *  $tableFormatter->transpose()->getTable() // Transposed table
	 *  $tableFormatter->transpose( false )->getTable() // Standard table
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
	 * Sets if the table should be transposed
	 *
	 * @since 1.9
	 *
	 * @param boolean $transpose
	 *
	 * @return TableFormatter
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
	public function addHeaderItem( $element, $content = '', $attributes = array() ) {
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
	 * @return TableFormatter
	 */
	public function addTableCell( $content = '', $attributes = array() ) {
		if ( $content !== '' ) {
			$this->tableCells[] = $this->getCell( $content, $attributes );
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
	 * @return TableFormatter
	 */
	public function addTableHeader( $content = '', $attributes = array() ) {
		if ( $content !== '' ) {
			$this->rawHeaders[] = array( 'content' => $content, 'attributes' => $attributes );
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
	 *  $tableFormatter->addTableCell( 'Lula' )->addTableCell( 'Lala' )->addTableRow()
	 *  ...
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param array $attributes
	 *
	 * @return TableFormatter
	 */
	public function addTableRow( $attributes = array() ) {
		if ( $this->tableCells !== array() ) {
			$this->rawRows[] = array( 'cells' => $this->tableCells, 'attributes' => $attributes );
			$this->tableCells = array();
		}
		return $this;
	}

	/**
	 * Internal method for returning a table row definition
	 *
	 * @since 1.9
	 *
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return string
	 */
	protected function getRow( $content = '', $attributes = array() ) {
		$alternate = count( $this->tableRows ) % 2 == 0 ? 'row-odd' : 'row-even';

		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = $attributes['class'] . ' ' . $alternate;
		} else {
			$attributes['class'] = $alternate;
		}

		return Html::rawElement( 'tr', $attributes , $content );
	}

	/**
	 * Internal method for returning a table cell definition
	 *
	 * @since 1.9
	 *
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return string
	 */
	protected function getCell( $content = '', $attributes = array() ) {
		return Html::rawElement( 'td', $attributes, $content );
	}

	/**
	 * Internal method for returning a table header definition
	 *
	 * @since 1.9
	 *
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return string
	 */
	protected function getHeader( $content = '', $attributes = array() ) {
		return Html::rawElement( 'th', $attributes, $content );
	}

	/**
	 * Returns table headers as concatenated string
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getTableHeader() {
		return $this->htmlContext ? Html::rawElement( 'thead', array(), implode( '', $this->tableHeaders ) ) : implode( '', $this->tableHeaders );
	}

	/**
	 * Returns table rows as concatenated string
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getTableRows() {
		return $this->htmlContext ? Html::rawElement( 'tbody', array(), implode( '', $this->tableRows ) ) : implode( '', $this->tableRows );
	}

	/**
	 * Returns a standard table
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getStandardTable() {
		$this->tableHeaders = array();
		$this->tableRows = array();

		foreach( $this->rawHeaders as $i => $header ) {
			$this->tableHeaders[] = $this->getHeader( $header['content'], $header['attributes'] );
		}

		foreach( $this->rawRows as $row ) {
			$this->tableRows[] = $this->getRow( implode( '', $row['cells'] ), $row['attributes'] );
		}

		return $this->getTableHeader() . $this->getTableRows();
	}

	/**
	 * Returns a transposed table
	 *
	 * @note A table will only be transposed if header elements are available
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getTransposedTable() {
		$this->tableRows = array();

		foreach( $this->rawHeaders as $hIndex => $header ) {
			$cells = array();
			$headerItem =  $this->getHeader( $header['content'], $header['attributes'] );

			foreach( $this->rawRows as $rIndex => $row ) {
				$cells[] = isset( $row['cells'][$hIndex] ) ? $row['cells'][$hIndex] : $this->getCell( '' );
			}

			// Collect new rows
			$this->tableRows[] = $this->getRow( $headerItem . implode( '', $cells ) );
		}

		return $this->getTableHeader() . $this->getTableRows();
	}

	/**
	 * Returns a table
	 *
	 * @par Example:
	 * @code
	 *  ...
	 *  $tableFormatter->getTable() // Standard table
	 *  $tableFormatter->transpose()->getTable() // Transposed table
	 *  $tableFormatter->transpose( false )->getTable() // Standard table
	 *  ...
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function getTable( $attributes = array() ) {

		$table = $this->transpose ? $this->getTransposedTable() : $this->getStandardTable();

		if ( $table !== '' ) {
			return Html::rawElement( 'table', $attributes, $table );
		}

		return '';
	}
}
