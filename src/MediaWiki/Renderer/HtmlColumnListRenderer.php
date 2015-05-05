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
	private $contentsByIndex = array();

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
	private $columnListClass = 'smw-columnlist-container';

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
	public function setListType( $listType ) {

		if ( in_array( $listType, array( 'ul', 'ol' ) ) ) {
			$this->listType = $listType;
		}

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

		$contentsByEmptyIndex[''] = array();

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

		$this->rowsPerColumn = ceil( $this->numberOfResults / $this->numberOfColumns );
		$this->columnWidth = floor( 100 / $this->numberOfColumns );
		$listContinuesAbbrev = wfMessage( 'listingcontinuesabbrev' )->text();

		foreach ( $this->contentsByIndex as $key => $resultItems ) {

			if ( $resultItems === array() ) {
					continue;
			}

			$result .= $this->doFormat(
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
			array( 'class' => $this->columnListClass ),
			$result . "\n" . '<br style="clear: both;"/>'
		);
	}

	private function doFormat( $key, $listContinuesAbbrev, $resultItems, &$usedColumnCloser ) {

		$result = '';
		$previousKey = "";

		foreach ( $resultItems as $resultItem ) {

			if ( $this->numRows % $this->rowsPerColumn == 0 ) {
				$result .= "<div class=\"smw-column\" style=\"width:$this->columnWidth%;\">";

				$numRowsInColumn = $this->numRows + 1;

				if ( $key == $previousKey ) {
					$result .= ( $key !== '' ? Html::element( 'div', array( 'class' => 'smw-column-header' ), "$key $listContinuesAbbrev" ) : '' ) . "<{$this->listType} start={$numRowsInColumn}>";
				}
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $key != $previousKey ) {
				$result .= $this->numRows % $this->rowsPerColumn > 0 ? "</{$this->listType}>" : '';
				$result .= ( $key !== '' ? Html::element( 'div', array( 'class' => 'smw-column-header' ), $key ) : '' ) . "<{$this->listType}>";
			}

			$previousKey = $key;
			$result .= Html::rawElement( 'li', array(), $resultItem );
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
