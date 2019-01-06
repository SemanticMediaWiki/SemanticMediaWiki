<?php

namespace SMW\MediaWiki\Renderer;

use Html;

/**
 * Simple list formatter to transform an indexed array (e.g. array( 'F' => array( 'Foo', 'Bar' ) )
 * into a column divided list.
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class HtmlColumnListRenderer {

	/**
	 * @var integer
	 */
	private $numberOfColumns = 1;

	/**
	 * @var array
	 */
	private $contentsByIndex = [];

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
	private $numberOfResults = 0;

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
	 * @since 2.2
	 *
	 * @param string $columnListClass
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function setColumnListClass( $columnListClass ) {
		$this->columnListClass = htmlspecialchars( $columnListClass );
		return $this;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $columnListClass
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function setColumnClass( $columnClass ) {
		$this->columnClass = htmlspecialchars( $columnClass );
		return $this;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $isRTL
	 */
	public function setColumnRTLDirectionalityState( $isRTL ) {
		$this->isRTL = (bool)$isRTL;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $numberOfColumns
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function setNumberOfColumns( $numberOfColumns ) {
		$this->numberOfColumns = $numberOfColumns;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $listType
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function setListType( $listType, $olType = '' ) {

		if ( in_array( $listType, [ 'ul', 'ol' ] ) ) {
			$this->listType = $listType;
		}

		if ( $this->listType === 'ol' && in_array( $olType, [ '1', 'a', 'A', 'i', 'I' ] ) ) {
			$this->olType = $olType;
		}

		return $this;
	}

	/**
	 * Allows to define attributes for a item such as:
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
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string[] $contentsByNoIndex
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function addContentsByNoIndex( array $contentsByNoIndex ) {

		$contentsByEmptyIndex[''] = [];

		foreach ( $contentsByNoIndex as $value ) {
			$contentsByEmptyIndex[''][] = $value;
		}

		return $this->addContentsByIndex( $contentsByEmptyIndex );
	}

	/**
	 * @since 2.1
	 *
	 * @param string[] $contentsByIndex
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function addContentsByIndex( array $contentsByIndex ) {
		$this->contentsByIndex = $contentsByIndex;
		$this->numberOfResults = count( $this->contentsByIndex, COUNT_RECURSIVE ) - count( $this->contentsByIndex );
		return $this;
	}

	/**
	 * @since  2.1
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
			$this->numberOfColumns = 1;
		} else {
			$this->columnWidth = floor( 100 / $this->numberOfColumns );
		}

		$this->rowsPerColumn = ceil( $this->numberOfResults / $this->numberOfColumns );
		$listContinuesAbbrev = wfMessage( 'listingcontinuesabbrev' )->text();

		foreach ( $this->contentsByIndex as $key => $resultItems ) {

			if ( $resultItems === [] ) {
					continue;
			}

			$result .= $this->makeList(
				$key,
				$listContinuesAbbrev,
				$resultItems,
				$usedColumnCloser
			);
		}

		if ( !$usedColumnCloser ) {
			$result .= "</{$this->listType}></div> <!-- end column -->";
		}

		return Html::rawElement(
			'div',
			[
				'class' => $this->columnListClass,
				'dir'   => $this->isRTL ? 'rtl' : 'ltr'
			],
			$result . "\n" . '<br style="clear: both;"/>'
		);
	}

	private function makeList( $key, $listContinuesAbbrev, $items, &$usedColumnCloser ) {

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
					// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
					$result .= $key !== '' ? Html::element( 'div', [ 'class' => 'smw-column-header' ], "$key $listContinuesAbbrev" ) : '';
					$result .= "<{$this->listType}$type start={$numRowsInColumn}>";
					// @codingStandardsIgnoreEnd
				}
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $key != $previousKey ) {
				$result .= $this->numRows % $this->rowsPerColumn > 0 ? "</{$this->listType}>" : '';
				$result .= ( $key !== '' ? Html::element( 'div', [ 'class' => 'smw-column-header' ], $key ) : '' ) . "<{$this->listType}>";
			}

			$previousKey = $key;
			$result .= Html::rawElement( 'li', $attributes, $item );
			$usedColumnCloser = false;

			if ( ( $this->numRows + 1 ) % $this->rowsPerColumn == 0 && ( $this->numRows + 1 ) < $this->numberOfResults ) {
				$result .= "</{$this->listType}></div> <!-- end column -->";
				$usedColumnCloser = true;
			}

			$this->numRows++;
		}

		return $result;
	}

}
