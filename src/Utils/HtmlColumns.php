<?php

namespace SMW\Utils;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlColumns {

	/**
	 * Indexed content
	 */
	const INDEXED_LIST = 'list/indexed';

	/**
	 * List content
	 */
	const PLAIN_LIST = 'list/plain';

	/**
	 * @var integer
	 */
	private $columns = 1;

	/**
	 * @var array
	 */
	private $contents = [];

	/**
	 * @var array
	 */
	private $itemAttributes = [];

	/**
	 * @var integer
	 */
	private $numRows = 0;

	/**
	 * @var integer
	 */
	private $count = 0;

	/**
	 * @var integer
	 */
	private $columnStyle = 0;

	/**
	 * @var string
	 */
	private $listType = 'ul';

	/**
	 * @var string
	 */
	private $olType = '';

	/**
	 * @var string
	 */
	private $continueAbbrev = '';

	/**
	 * @var string
	 */
	private $columnListClass = 'smw-columnlist-container';

	/**
	 * @var string
	 */
	private $columnClass = 'smw-column';

	/**
	 * @var boolean
	 */
	private $isRTL = false;

	/**
	 * @var boolean
	 */
	private $isResponsiveCols = false;

	/**
	 * @var integer
	 */
	private $responsiveColsThreshold = 10;

	/**
	 * @since 3.0
	 *
	 * @param string $columnListClass
	 */
	public function setColumnListClass( $columnListClass ) {
		$this->columnListClass = htmlspecialchars( $columnListClass );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $columnListClass
	 */
	public function setColumnClass( $columnClass ) {
		$this->columnClass = htmlspecialchars( $columnClass );
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isRTL
	 */
	public function isRTL( $isRTL ) {
		$this->isRTL = (bool)$isRTL;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isResponsiveCols
	 */
	public function setResponsiveCols( $isResponsiveCols = true ) {
		$this->isResponsiveCols = (bool)$isResponsiveCols;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $responsiveColsThreshold
	 */
	public function setResponsiveColsThreshold( $responsiveColsThreshold ) {
		$this->responsiveColsThreshold = (int)$responsiveColsThreshold;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $columns
	 */
	public function setColumns( $columns ) {
		$this->columns = $columns;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $listType
	 * @param string $olType
	 */
	public function setListType( $listType, $olType = '' ) {

		if ( in_array( $listType, [ 'ul', 'ol' ] ) ) {
			$this->listType = $listType;
		}

		if ( $this->listType === 'ol' && in_array( $olType, [ '1', 'a', 'A', 'i', 'I' ] ) ) {
			$this->olType = $olType;
		}
	}

	/**
	 * Allows to define attributes for an item such as:
	 *
	 * [md5( $itemContent )] = [
	 * 	'id' => 'Foo'
	 * ]
	 *
	 * @since 3.0
	 *
	 * @param array $itemAttributes
	 */
	public function setItemAttributes( array $itemAttributes ) {
		$this->itemAttributes = $itemAttributes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $continueAbbrev
	 */
	public function setContinueAbbrev( $continueAbbrev ) {
		$this->continueAbbrev = $continueAbbrev;
	}

	/**
	 * @since 3.0
	 *
	 * @param string[] $cnts
	 * @param string $type
	 */
	public function addContents( array $cnts, $type = self::PLAIN_LIST ) {
		$this->setContents( $cnts, $type );
	}

	/**
	 * @since 3.0
	 *
	 * @param string[] $cnts
	 * @param string $type
	 */
	public function setContents( array $cnts, $type = self::PLAIN_LIST ) {

		if ( $type === self::PLAIN_LIST ) {
			$contents[''] = [];

			foreach ( $cnts as $value ) {
				$contents[''][] = $value;
			}

		} elseif ( $type === self::INDEXED_LIST ) {
			$contents = $cnts;
		} else {
			throw new InvalidArgumentException( 'Missing a recognized type!');
		}

		$this->contents = $contents;
		$this->count = count( $this->contents, COUNT_RECURSIVE ) - count( $this->contents );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getHtml() {

		$result = '';
		$usedColumnCloser = false;
		$this->numRows = 0;

		// Directly influence the max columns especially in combination of `isResponsiveCols`
		// so that the responsiveness is kept while limiting the output to only
		// show a maximum of ... columns
		$maxColumns = $this->columns;

		if ( $this->isResponsiveCols && $this->count < $this->responsiveColsThreshold ) {
			// A list with less than ... items will not benefit from a responsive
			// column design
			$maxColumns = 1;
			$width = 100;
			$this->columns = 1;
			$this->columnStyle = "width:$width%;columns:$maxColumns 20em;";
			$this->columnClass = 'smw-column-responsive';
		} elseif ( $this->isResponsiveCols ) {
			$width = 100;
			$this->columns = 1;
			$this->columnClass = 'smw-column-responsive';
			$this->columnStyle = "width:$width%;columns:$maxColumns 20em;";
		} else {
			$width = floor( 100 / $this->columns );
			$this->columnStyle = "width:$width%;";
		}

		$rowsPerColumn = ceil( $this->count / $this->columns );

		foreach ( $this->contents as $key => $items ) {

			if ( $items === [] ) {
					continue;
			}

			$result .= $this->makeList(
				$key,
				$items,
				$rowsPerColumn,
				$maxColumns,
				$usedColumnCloser
			);
		}

		if ( !$usedColumnCloser ) {
			$result .= "</{$this->listType}></div> <!-- end column -->";
		}

		return $this->element(
			'div',
			[
				'class' => $this->columnListClass,
				'dir'   => $this->isRTL ? 'rtl' : 'ltr'
			],
			$result . "\n" . '<br style="clear: both;"/>'
		);
	}

	private function makeList( $key, $items, $rowsPerColumn, $columns, &$usedColumnCloser ) {

		$result = '';
		$previousKey = "";
		$dir = $this->isRTL ? 'rtl' : 'ltr';

		foreach ( $items as $item ) {

			$attributes = [];

			if ( $this->itemAttributes !== [] ) {
				$hash = md5( $item );

				if ( isset( $this->itemAttributes[$hash] ) ) {
					$attributes = $this->itemAttributes[$hash];
				}
			}

			if ( $this->numRows % $rowsPerColumn == 0 ) {
				$result .= "<div class=\"$this->columnClass\" style=\"$this->columnStyle\" dir=\"$dir\">";

				$numRowsInColumn = $this->numRows + 1;
				$type = $this->olType !== '' ? " type={$this->olType}" : '';

				if ( $key == $previousKey ) {
					if ( $key !== '' ) {
						$result .= $this->element(
							'div',
							[
								'class' => 'smw-column-header'
							],
							"$key {$this->continueAbbrev}"
						);
					}

					$result .= "<{$this->listType}$type start={$numRowsInColumn}>";
				}
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $key != $previousKey ) {
				$result .= $this->numRows % $rowsPerColumn > 0 ? "</{$this->listType}>" : '';
				$result .= ( $key !== '' ? $this->element( 'div', [ 'class' => 'smw-column-header' ], $key ) : '' ) . "<{$this->listType}>";
			}

			$previousKey = $key;
			$result .= $this->element( 'li', $attributes, $item );
			$usedColumnCloser = false;

			if ( ( $this->numRows + 1 ) % $rowsPerColumn == 0 && ( $this->numRows + 1 ) < $this->count ) {
				$result .= "</{$this->listType}></div> <!-- end column -->";
				$usedColumnCloser = true;
			}

			$this->numRows++;
		}

		return $result;
	}

	private function element( $type, $attributes, $content ) {

		$attr = '';
		$attributes = (array)$attributes;

		if ( $attributes !== [] ) {
			foreach ( $attributes as $key => $value ) {
				$attr .= ' ' . $key . '="' . $value . '"';
			}
		}

		return "<$type$attr>$content</$type>";
	}

}
