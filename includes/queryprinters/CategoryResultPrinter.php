<?php

namespace SMW;

use SMW\MediaWiki\Collator;
use SMWDataItem;
use SMWQueryResult;

/**
 * Print query results in alphabetic groups displayed in columns, a la the
 * standard Category pages.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author David Loomer
 * @author Yaron Koren
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CategoryResultPrinter extends ResultPrinter {

	protected $mDelim;
	protected $mTemplate;
	protected $mUserParam;
	protected $mNumColumns;

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return wfMessage( 'smw_printername_' . $this->mFormat )->text();
	}

	/**
	 * @see ResultPrinter::isDeferrable
	 *
	 * {@inheritDoc}
	 */
	public function isDeferrable() {
		return true;
	}

	/**
	 * @see ResultPrinter::supportsRecursiveAnnotation
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsRecursiveAnnotation() {
		return true;
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			'name' => 'columns',
			'type' => 'integer',
			'message' => 'smw-paramdesc-columns',
			'default' => 3,
		];

		$definitions[] = [
			'name' => 'delim',
			'message' => 'smw-paramdesc-category-delim',
			'default' => '',
		];

		$definitions[] = [
			'name' => 'template',
			'message' => 'smw-paramdesc-category-template',
			'default' => '',
		];

		$definitions[] = [
			'name' => 'userparam',
			'message' => 'smw-paramdesc-category-userparam',
			'default' => '',
		];

		$definitions[] = [
			'name' => 'named args',
			'type' => 'boolean',
			'message' => 'smw-paramdesc-named_args',
			'default' => false,
		];

		return $definitions;
	}

	/**
	 * @see ResultPrinter::handleParameters
	 *
	 * {@inheritDoc}
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->mUserParam = trim( $params['userparam'] );
		$this->mDelim = trim( $params['delim'] );
		$this->mNumColumns = $params['columns'];
		$this->mTemplate = $params['template'];
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( SMWQueryResult $res, $outputMode ) {
		$contentsByIndex = array();

		// Print all result rows:
		$rowindex = 0;
		$row = $res->getNext();

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

		$htmlColumnListRenderer = $mwCollaboratorFactory->newHtmlColumnListRenderer();
		$templateRenderer = $mwCollaboratorFactory->newWikitextTemplateRenderer();

		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead

			if ( !isset( $row[0] ) ) {
				$row = $nextrow;
				continue;
			}

			$content = $row[0]->getContent();

			if ( !isset( $content[0] ) || !( $content[0] instanceof SMWDataItem ) ) {
				$row = $nextrow;
				continue;
			}

			$columnIndex = $this->getFirstLetterForCategory( $res, $content[0] );

			if ( !isset( $contentsByIndex[$columnIndex] ) ) {
				$contentsByIndex[$columnIndex] = array();
				$lastColumnIndex = $columnIndex;
			}

			if ( $this->mTemplate !== '' ) { // build template code

				$first_col = true;
				$this->hasTemplates = true;

				if ( $this->mUserParam ) {
					$templateRenderer->addField( 'userparam', $this->mUserParam );
				}

				$this->addRowFieldsToTemplate(
					$res,
					$row,
					$first_col,
					$templateRenderer
				);

				$templateRenderer->addField( '#', $rowindex );
				$templateRenderer->packFieldsForTemplate( $this->mTemplate );

				// str_replace('|', '&#x007C;', // encode '|' for use in templates (templates fail otherwise) --
				// this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
				$contentsByIndex[$columnIndex][] = $templateRenderer->render();
			} else {  // build simple list
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?
				$result = '';

				foreach ( $row as $field ) {
					$first_value = true;
					$fieldValues = array();

					while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {

						if ( !$first_col && !$found_values ) { // first values after first column
							$result .= '(';
							$found_values = true;
						}

						if ( $first_value ) { // first value in any column, print header
							$first_value = false;

							if ( $this->mShowHeaders && ( $field->getPrintRequest()->getLabel() !== '' ) ) {
								$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) ) . ' ';
							}
						}

						$fieldValues[] = $text;
					}

					$first_col = false;

					// Always sort the column value list in the same order
					natsort( $fieldValues );
					$result .= implode( ( $this->mDelim ? $this->mDelim : ',' ) . ' ', $fieldValues ) . ' ';
				}

				if ( $found_values ) {
					$result = trim( $result ) . ')';
				}

				$contentsByIndex[$columnIndex][] = $result;
			}

			$row = $nextrow;
			$rowindex++;
		}

		if ( $contentsByIndex === array() ) {

			$res->addErrors( array(
				$this->msg( 'smw-qp-empty-data' )->inContentLanguage()->text()
			) );

			return '';
		}

		// Make label for finding further results
		if ( $this->linkFurtherResults( $res ) ) {
			$contentsByIndex[$lastColumnIndex][] = $this->getFurtherResultsLink( $res, $outputMode )->getText( SMW_OUTPUT_WIKI, $this->mLinker );
		}

		$htmlColumnListRenderer->setNumberOfColumns( $this->mNumColumns );
		$htmlColumnListRenderer->addContentsByIndex( $contentsByIndex );

		// Per convention, an explicit 0 setting forces the columns to behave responsive
		if ( $this->params['columns'] == 0 ) {
			$htmlColumnListRenderer->setColumnClass( 'smw-column-responsive' );
			$htmlColumnListRenderer->setNumberOfColumns( 1 );
		}

		return $htmlColumnListRenderer->getHtml();
	}


	private function getFirstLetterForCategory( SMWQueryResult $res, SMWDataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$sortKey = $res->getStore()->getWikiPageSortKey( $dataItem );
		}

		return Collator::singleton()->getFirstLetter( $sortKey );
	}

	private function addRowFieldsToTemplate( $res, $row, &$first_col, $templateRenderer ) {

		// explicitly number parameters for more robust parsing (values may contain "=")
		$i = 0;

		foreach ( $row as $field ) {
			$i++;

			$fieldName = '';

			if ( $this->params['named args'] ) {
				$fieldName = $field->getPrintRequest()->getLabel();
			}

			if ( $fieldName === '' || $fieldName === '?' ) {
				$fieldName = $fieldName . $i;
			}

			$fieldValues = array();

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
				$fieldValues[] = $text;
			}

			natsort( $fieldValues );

			$templateRenderer->addField( $fieldName, implode( $this->mDelim . ' ', $fieldValues ) );
			$first_col = false;
		}
	}

}
