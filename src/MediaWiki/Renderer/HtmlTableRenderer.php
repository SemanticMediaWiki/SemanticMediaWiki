<?php

namespace SMW\MediaWiki\Renderer;

use MediaWiki\Html\Html;

/**
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class HtmlTableRenderer {

	private array $headerItems = [];

	private array $tableRows = [];

	private array $rawRows = [];

	private array $tableHeaders = [];

	private array $rawHeaders = [];

	private array $tableCells = [];

	private bool $transpose = false;

	/**
	 * @par Example:
	 * @code
	 * $htmlTableRenderer = new HtmlTableRenderer();
	 *
	 * $htmlTableRenderer
	 *  	->addHeader( 'Foo' )
	 *  	->addHeader( 'Bar' )
	 *  	->addCell( 'Lula' )
	 *  	->addCell( 'Lala' )
	 *  	->addRow();
	 *
	 * $htmlTableRenderer->getHtml()
	 * @endcode
	 *
	 * @since 1.9
	 */
	public function __construct( private bool $htmlContext = false ) {
	}

	/**
	 * @since 2.1
	 */
	public function setHtmlContext( bool $htmlContext ): static {
		$this->htmlContext = $htmlContext;
		return $this;
	}

	/**
	 * @since 1.9
	 */
	public function transpose( bool $transpose = true ): static {
		$this->transpose = $transpose;
		return $this;
	}

	/**
	 * Adds an arbitrary header item to an internal array
	 *
	 * @since 1.9
	 */
	public function addHeaderItem( string $element, string $content = '', array $attributes = [] ): void {
		$this->headerItems[] = Html::rawElement( $element, $attributes, $content );
	}

	/**
	 * Returns concatenated header items
	 *
	 * @since 1.9
	 */
	public function getHeaderItems(): string {
		return implode( '', $this->headerItems );
	}

	/**
	 * Collects and adds table cells
	 *
	 * @since 1.9
	 */
	public function addCell( string $content = '', array $attributes = [] ): static {
		if ( $content !== '' ) {
			$this->tableCells[] = $this->createCell( $content, $attributes );
		}
		return $this;
	}

	/**
	 * Collects and adds table headers
	 *
	 * @since 1.9
	 */
	public function addHeader( string $content = '', array $attributes = [] ): static {
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
	 *  $htmlTableRenderer->addCell( 'Lula' )->addCell( 'Lala' )->addRow()
	 *  ...
	 * @endcode
	 *
	 * @since 1.9
	 */
	public function addRow( array $attributes = [] ): static {
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
	 */
	public function getHtml( array $attributes = [] ): string {
		$table = $this->transpose ? $this->buildTransposedTable() : $this->buildStandardTable();

		if ( $this->transpose ) {
			$attributes['data-transpose'] = true;
		}

		if ( $table !== '' ) {
			return Html::rawElement( 'table', $attributes, $table );
		}

		return '';
	}

	private function createRow( string $content = '', array $attributes = [] ): string {
		$alternate = count( $this->tableRows ) % 2 == 0 ? 'row-odd' : 'row-even';

		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] = $attributes['class'] . ' ' . $alternate;
		} else {
			$attributes['class'] = $alternate;
		}

		return Html::rawElement( 'tr', $attributes, $content );
	}

	private function createCell( $content = '', $attributes = [] ): string {
		return Html::rawElement( 'td', $attributes, $content );
	}

	private function createHeader( $content = '', $attributes = [] ): string {
		return Html::rawElement( 'th', $attributes, $content );
	}

	private function doConcatenatedRows(): string {
		if ( $this->htmlContext ) {
			return Html::rawElement( 'tbody', [], implode( '', $this->tableRows ) );
		}

		return implode( '', $this->tableRows );
	}

	private function buildStandardTable(): string {
		$this->tableHeaders = [];
		$this->tableRows = [];

		foreach ( $this->rawHeaders as $i => $header ) {
			$this->tableHeaders[] = $this->createHeader( $header['content'], $header['attributes'] );
		}

		foreach ( $this->rawRows as $row ) {
			$this->tableRows[] = $this->createRow( implode( '', $row['cells'] ), $row['attributes'] );
		}

		return $this->doConcatenatedHeader() . $this->doConcatenatedRows();
	}

	private function doConcatenatedHeader(): string {
		if ( $this->htmlContext ) {
			return Html::rawElement(
				'thead',
				[],
				$this->getHeaderRowHtml()
			);
		}

		return $this->getHeaderRowHtml();
	}

	private function getHeaderRowHtml(): string {
		if ( $this->tableHeaders === [] ) {
			return '';
		}

		return Html::rawElement(
			'tr',
			[],
			implode( '', $this->tableHeaders )
		);
	}

	private function buildTransposedTable(): string {
		$this->tableRows = [];

		foreach ( $this->rawHeaders as $hIndex => $header ) {
			$cells = [];
			$headerItem = $this->createHeader( $header['content'], $header['attributes'] );

			foreach ( $this->rawRows as $rIndex => $row ) {
				$cells[] = $this->getTransposedCell( $hIndex, $row );
			}

			// Collect new rows
			$this->tableRows[] = $this->createRow( $headerItem . implode( '', $cells ) );
		}

		return $this->doConcatenatedHeader() . $this->doConcatenatedRows();
	}

	private function getTransposedCell( int|string $index, array $row ): string {
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
