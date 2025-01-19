<?php

namespace SMW\MediaWiki\Specials;

use Html;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Utils\HtmlColumns;
use SpecialPage;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialMissingRedirectAnnotations extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'MissingRedirectAnnotations' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		$this->setHeaders();
		$output = $this->getOutput();

		$output->addModuleStyles( [ 'ext.smw.styles' ] );

		$applicationFactory = ApplicationFactory::getInstance();
		$dataValueFactory = DataValueFactory::getInstance();

		$store = $applicationFactory->getStore();
		$linker = smwfGetLinker();

		$sortLetter = $store->service( 'SortLetter' );
		$missingRedirectLookup = $store->service( 'MissingRedirectLookup' );

		$missingRedirectLookup->noSort();

		$missingRedirectLookup->setNamespaceMatrix(
			$applicationFactory->getSettings()->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$rows = $missingRedirectLookup->findMissingRedirects();
		$count = $rows->numRows();

		$contents = [];

		foreach ( $rows as $row ) {
			$dataItem = new DIWikiPage( $row->page_title, $row->page_namespace );
			$startChar = $sortLetter->getFirstLetter( $dataItem );

			if ( !isset( $contents[$startChar] ) ) {
				$contents[$startChar] = [];
			}

			$dataValue = $dataValueFactory->newDataValueByItem( $dataItem );
			$dataValue->setQueryParameters( [ 'redirect' => 'no' ] );

			$contents[$startChar][] = $dataValue->getLongHtmlText( $linker );
		}

		ksort( $contents );

		$output->addHtml(
			$this->makeSpecialPageBreadcrumbLink()
		);

		$output->addHtml(
			$this->buildHTML( $count, $contents )
		);

		return true;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/maintenance';
	}

	private function buildHTML( $count, $contents ) {
		$htmlColumns = new HtmlColumns();
		$htmlColumns->setContents( $contents, HtmlColumns::INDEXED_LIST );

		$html = Html::rawElement(
			'p',
			[
				'class' => 'plainlinks'
			],
			$this->msg( 'smw-missingredirects-intro' )->parse()
		);

		if ( $contents === [] ) {
			$html .= Html::rawElement(
				'p',
				[
					'style' => 'font-style:normal;margin-top:20px;'
				],
				$this->msg( 'smw-missingredirects-noresult' )->text()
			);
		} else {
			$html .= Html::rawElement(
				'h2',
				[],
				$this->msg( 'smw-missingredirects-list' )->text()
			) . Html::rawElement(
				'p',
				[],
				$this->msg( 'smw-missingredirects-list-intro', $count )->text()
			) . Html::rawElement(
				'div',
				[],
				$htmlColumns->getHtml()
			);
		}

		return $html;
	}

	private static function makeSpecialPageBreadcrumbLink( $query = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => 'smw-breadcrumb-link'
			],
			Html::rawElement(
				'span',
				[
					'class' => 'smw-breadcrumb-arrow-right'
				]
			) . Html::rawElement(
				'a',
				[
					'href' => \SpecialPage::getTitleFor( 'Specialpages' )->getFullURL( $query ) . '#Semantic_MediaWiki'
				],
				Message::get( 'specialpages', Message::TEXT, Message::USER_LANGUAGE )
		) );
	}

}
