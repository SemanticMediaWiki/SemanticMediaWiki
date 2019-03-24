<?php

namespace SMW\Query\ResultPrinters;

use SMW\MediaWiki\Collator;
use SMWDataItem as DataItem;
use SMWQueryResult as QueryResult;
use SMW\Utils\HtmlColumns;
use SMW\ApplicationFactory;
use SMW\Localizer;

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

	/**
	 * @var string
	 */
	private $delim;

	/**
	 * @var string
	 */
	private $template;

	/**
	 * @var string
	 */
	private $userParam;

	/**
	 * @var integer
	 */
	private $numColumns;

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
			'negatives' => false,
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

		$this->userParam = isset( $params['userparam'] ) ? trim( $params['userparam'] ) : '';
		$this->delim = isset( $params['delim'] ) ? trim( $params['delim'] ) : '';
		$this->numColumns = isset( $params['columns'] ) ? $params['columns'] : 3;
		$this->template = isset( $params['template'] ) ? $params['template'] : '';
	}

	/**
	 * @since 3.0
	 */
	protected function initServices() {
		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

		$this->htmlColumns = new HtmlColumns();
		$this->templateRenderer = $mwCollaboratorFactory->newWikitextTemplateRenderer();
		$this->collator = Collator::singleton();
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		$this->initServices();
		$contents = $this->getContents( $res, $outputMode );

		if ( $contents === [] ) {
			return $res->addErrors( [ 'smw-qp-empty-data' ] );
		}

		$language = Localizer::getInstance()->getUserLanguage();

		$this->htmlColumns->setContinueAbbrev( wfMessage( 'listingcontinuesabbrev' )->text() );
		$this->htmlColumns->setColumns( $this->numColumns );
		$this->htmlColumns->isRTL( $language->isRTL() );

		// 0 indicates to use responsive columns
		$this->htmlColumns->setResponsiveCols(
			$this->params['columns'] == 0
		);

		$this->htmlColumns->setResponsiveColsThreshold( 5 );
		$this->htmlColumns->addContents( $contents, HtmlColumns::INDEXED_LIST );

		return $this->htmlColumns->getHtml();
	}

	private function getContents( QueryResult $res, $outputMode ) {
		$contents = [];

		// Print all result rows:
		$rowindex = 0;
		$row = $res->getNext();

		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead

			if ( !isset( $row[0] ) ) {
				$row = $nextrow;
				continue;
			}

			$content = $row[0]->getContent();

			if ( !isset( $content[0] ) || !( $content[0] instanceof DataItem ) ) {
				$row = $nextrow;
				continue;
			}

			$first_letter = $this->first_letter( $res, $content[0] );

			if ( !isset( $contents[$first_letter] ) ) {
				$contents[$first_letter] = [];
				$last_letter = $first_letter;
			}

			if ( $this->template !== '' ) { // build template code

				$first_col = true;
				$this->hasTemplates = true;

				if ( $this->userParam ) {
					$this->templateRenderer->addField( 'userparam', $this->userParam );
				}

				$this->row_to_template( $row, $res, $first_col );

				$this->templateRenderer->addField( '#', $rowindex );
				$this->templateRenderer->packFieldsForTemplate( $this->template );

				// str_replace('|', '&#x007C;', // encode '|' for use in templates (templates fail otherwise) --
				// this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
				$contents[$first_letter][] = $this->templateRenderer->render();
			} else {  // build simple list
				$first_col = true;
				$contents[$first_letter][] = $this->row_to_contents( $row, $first_col );
			}

			$row = $nextrow;
			$rowindex++;
		}

		// Make label for finding further results
		if ( $this->linkFurtherResults( $res ) ) {
			$contents[$last_letter][] = $this->getFurtherResultsLink( $res, $outputMode )->getText( SMW_OUTPUT_WIKI, $this->mLinker );
		}

		return $contents;
	}

	private function first_letter( QueryResult $res, DataItem $dataItem ) {

		$sortKey = $dataItem->getSortKey();

		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
			$sortKey = $res->getStore()->getWikiPageSortKey( $dataItem );
		}

		return $this->collator->getFirstLetter( $sortKey );
	}

	private function row_to_contents( $row, &$first_col ) {

		// has anything but the first column been printed?
		$found_values = false;
		$result = '';

		foreach ( $row as $field ) {
			$first_value = true;
			$fieldValues = [];

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {

				// first values after first column
				if ( !$first_col && !$found_values ) {
					$result .= '(';
					$found_values = true;
				}

				// first value in any column, print header
				if ( $first_value ) {
					$first_value = false;
					$printRequest = $field->getPrintRequest();

					if ( $this->mShowHeaders && ( $printRequest->getLabel() !== '' ) ) {
						$linker = $this->mShowHeaders === SMW_HEADERS_PLAIN ? null : $this->mLinker;
						$result .= $printRequest->getText( SMW_OUTPUT_WIKI, $linker );
						$result .= ' ';
					}
				}

				$fieldValues[] = $text;
			}

			$first_col = false;

			// Always sort the column value list in the same order
			natsort( $fieldValues );
			$result .= implode( ( $this->delim ? $this->delim : ',' ) . ' ', $fieldValues ) . ' ';
		}

		if ( $found_values ) {
			$result = trim( $result ) . ')';
		}

		return $result;
	}

	private function row_to_template( $row, $res, &$first_col ) {

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

			$fieldValues = [];

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
				$fieldValues[] = $text;
			}

			natsort( $fieldValues );

			$this->templateRenderer->addField( $fieldName, implode( $this->delim . ' ', $fieldValues ) );
			$first_col = false;
		}
	}

}
