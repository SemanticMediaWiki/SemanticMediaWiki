<?php

namespace SMW;

use SMW\MediaWiki\ByLanguageCollationMapper;
use SMWQueryResult;
use SMWDataItem;

/**
 * Print query results in alphabetic groups displayed in columns, a la the
 * standard Category pages and the default view in Semantic Drilldown.
 * Based on SMW_QP_List by Markus Krötzsch.
 *
 * @ingroup SMWQuery
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
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.6.2
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->mUserParam = trim( $params['userparam'] );
		$this->mDelim = trim( $params['delim'] );
		$this->mNumColumns = $params['columns'];
		$this->mTemplate = $params['template'];
	}

	public function getName() {
		return wfMessage( 'smw_printername_' . $this->mFormat )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputMode ) {

		$result = '';
		$contentsByIndex = array();
		$columnIndex = '';

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

			$cur_first_char = $this->getFirstLetterForCategory( $res, $content );

			if ( !isset( $contentsByIndex[$cur_first_char] ) ) {
				$contentsByIndex[$cur_first_char] = array();
			}

			if ( $this->mTemplate !== '' ) { // build template code

				$first_col = true;
				$this->hasTemplates = true;

				if ( $this->mUserParam ) {
					$templateRenderer->addField( 'userparam', $this->mUserParam );
				}

				$this->addRowFieldsToTemplate(
					$row,
					$first_col,
					$columnIndex,
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

					$columnIndex = ByLanguageCollationMapper::getInstance()->findFirstLetterForCategory(
						$field->getResultSubject()->getSortKey()
					);

					while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
						if ( !$first_col && !$found_values ) { // first values after first column
							$result .= ' (';
							$found_values = true;
						} elseif ( $found_values || !$first_value ) {
							// any value after '(' or non-first values on first column
							$result .= ', ';
						}

						if ( $first_value ) { // first value in any column, print header
							$first_value = false;

							if ( $this->mShowHeaders && ( $field->getPrintRequest()->getLabel() !== '' ) ) {
								$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) ) . ' ';
							}
						}

						$result .= $text; // actual output value
					}

					$first_col = false;
				}

				if ( $found_values ) {
					$result .= ')';
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
			$contentsByIndex[$columnIndex][] = $this->getLink( $res, $outputMode )->getText( SMW_OUTPUT_WIKI, $this->mLinker );
		}

		$htmlColumnListRenderer->setNumberOfColumns( $this->mNumColumns );
		$htmlColumnListRenderer->addContentsByIndex( $contentsByIndex );

		return $htmlColumnListRenderer->getHtml();
	}

	public function getParameters() {
		return array_merge( parent::getParameters(), array(
			array(
				'name' => 'columns',
				'type' => 'integer',
				'message' => 'smw-paramdesc-columns',
				'default' => 3,
			),
			array(
				'name' => 'delim',
				'message' => 'smw-paramdesc-category-delim',
				'default' => '',
			),
			array(
				'name' => 'template',
				'message' => 'smw-paramdesc-category-template',
				'default' => '',
			),
			array(
				'name' => 'userparam',
				'message' => 'smw-paramdesc-category-userparam',
				'default' => '',
			),

			array(
				'name' => 'named args',
				'type' => 'boolean',
				'message' => 'smw-paramdesc-named_args',
				'default' => false,
			)
		) );
	}

	private function getFirstLetterForCategory( SMWQueryResult $res, $content ) {

		$sortKey = $content[0]->getSortKey();

		if ( $content[0]->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$sortKey = $res->getStore()->getWikiPageSortKey( $content[0] );
		}

		return ByLanguageCollationMapper::getInstance()->findFirstLetterForCategory( $sortKey );
	}

	private function addRowFieldsToTemplate( $row, &$first_col, &$columnIndex, $templateRenderer ) {

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

			$first_value = true;
			$fieldValue = '';

			$columnIndex = ByLanguageCollationMapper::getInstance()->findFirstLetterForCategory(
				$field->getResultSubject()->getSortKey()
			);

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {

				if ( $first_value ) {
					$first_value = false;
				} else {
					$fieldValue .= $this->mDelim . ' ';
				}

				$fieldValue .= $text;
			}

			$templateRenderer->addField( $fieldName, $fieldValue );
			$first_col = false;
		}
	}

}
