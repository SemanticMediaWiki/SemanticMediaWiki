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
	const INDX_CONTENT = 'indexed.list';

	/**
	 * Indexed content
	 */
	const INDEXED_LIST = 'indexed.list';

	/**
	 * List content
	 */
	const LIST_CONTENT = 'list.content';

	/**
	 * List content
	 */
	const PLAIN_LIST = 'plain.list';

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
	private $rowsPerColumn = 0;

	/**
	 * @var integer
	 */
	private $columnWidth = 0;

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
	public function addContents( array $cnts, $type = self::LIST_CONTENT ) {
		$this->setContents( $cnts, $type );
	}

	/**
	 * @since 3.0
	 *
	 * @param string[] $cnts
	 * @param string $type
	 */
	public function setContents( array $cnts, $type = self::LIST_CONTENT ) {

		if ( $type === self::LIST_CONTENT ) {
			$contents[''] = [];

			foreach ( $cnts as $value ) {
				$contents[''][] = $value;
			}

		} elseif ( $type === self::INDX_CONTENT ) {
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

		// Class to determine whether we want responsive columns width
		if ( strpos( $this->columnClass, 'responsive' ) !== false ) {
			$this->columnWidth = 100;
			$this->columns = 1;
		} else {
			$this->columnWidth = floor( 100 / $this->columns );
		}

		$this->rowsPerColumn = ceil( $this->count / $this->columns );

		foreach ( $this->contents as $key => $items ) {

			if ( $items === [] ) {
					continue;
			}

			$result .= $this->makeList(
				$key,
				$items,
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

	private function makeList( $key, $items, &$usedColumnCloser ) {

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

			if ( $this->numRows % $this->rowsPerColumn == 0 ) {
				$result .= "<div class=\"$this->columnClass\" style=\"width:$this->columnWidth%;\" dir=\"$dir\">";

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
				$result .= $this->numRows % $this->rowsPerColumn > 0 ? "</{$this->listType}>" : '';
				$result .= ( $key !== '' ? $this->element( 'div', [ 'class' => 'smw-column-header' ], $key ) : '' ) . "<{$this->listType}>";
			}

			$previousKey = $key;
			$result .= $this->element( 'li', $attributes, $item );
			$usedColumnCloser = false;

			if ( ( $this->numRows + 1 ) % $this->rowsPerColumn == 0 && ( $this->numRows + 1 ) < $this->count ) {
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
