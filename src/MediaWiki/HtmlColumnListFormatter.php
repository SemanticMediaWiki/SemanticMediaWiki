<?php

namespace SMW\MediaWiki;

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
class HtmlColumnListFormatter {

	/**
	 * @var integer
	 */
	private $numberOfColumns = 1;

	/**
	 * @var array
	 */
	private $arrayOfResults = array();

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
	 * @since 2.1
	 *
	 * @param integer $numberOfColumns
	 *
	 * @return HtmlColumnListFormatter
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
	 * @return HtmlColumnListFormatter
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
	 * @param string[] $arrayOfResults
	 *
	 * @return HtmlColumnListFormatter
	 */
	public function addIndexedArrayOfResults( array $arrayOfResults ) {
		$this->arrayOfResults = $arrayOfResults;
		$this->numberOfResults = count( $this->arrayOfResults, COUNT_RECURSIVE );
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

		foreach ( $this->arrayOfResults as $key => $resultItems ) {

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
			array( 'class' => 'smw-columnlist-container' ),
			$result . "\n" . '<br style="clear: both;"/>'
		);
	}

	private function doFormat( $key, $listContinuesAbbrev, $resultItems, &$usedColumnCloser ) {

		$result = '';
		$previousKey = "";

		foreach ( $resultItems as $resultItem ) {

			if ( $this->numRows % $this->rowsPerColumn == 0 ) {
				$result .= "<div class=\"smw-column\" style=\"float: left; width:$this->columnWidth%; word-wrap: break-word;\">";

				$numRowsInColumn = $this->numRows + 1;

				if ( $key == $previousKey ) {
					$result .= "<h3>$key " . $listContinuesAbbrev . "</h3><{$this->listType} start={$numRowsInColumn}>";
				}
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $key != $previousKey ) {
				$result .= $this->numRows % $this->rowsPerColumn > 0 ? "</{$this->listType}>" : '';
				$result .= "<h3>$key</h3><{$this->listType}>";
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
