<?php

namespace SMW\MediaWiki\Specials;

use MediaWiki\Html\Html;
use MediaWiki\Skin\SkinComponentUtils;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\Localizer\Message;
use SMW\Settings;
use SMW\Store;
use SMW\Utils\HtmlColumns;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialMissingRedirectAnnotations extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly Settings $settings
	) {
		parent::__construct( 'MissingRedirectAnnotations' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ): bool {
		$this->setHeaders();
		$output = $this->getOutput();

		$output->addModuleStyles( [ 'ext.smw.styles' ] );

		$dataValueFactory = DataValueFactory::getInstance();

		$linker = smwfGetLinker();

		$sortLetter = $this->store->service( 'SortLetter' );
		$missingRedirectLookup = $this->store->service( 'MissingRedirectLookup' );

		$missingRedirectLookup->noSort();

		$missingRedirectLookup->setNamespaceMatrix(
			$this->settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$rows = $missingRedirectLookup->findMissingRedirects();
		$count = $rows->numRows();

		$contents = [];

		foreach ( $rows as $row ) {
			$dataItem = new WikiPage( $row->page_title, $row->page_namespace );
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
	protected function getGroupName(): string {
		return 'smw_group/maintenance';
	}

	private function buildHTML( $count, array $contents ): string {
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
				$this->msg( 'smw-missingredirects-noresult' )->escaped()
			);
		} else {
			$html .= Html::rawElement(
				'h2',
				[],
				$this->msg( 'smw-missingredirects-list' )->escaped()
			) . Html::rawElement(
				'p',
				[],
				$this->msg( 'smw-missingredirects-list-intro', $count )->escaped()
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
			) . Html::element(
				'a',
				[
					'href' => SkinComponentUtils::makeSpecialUrl( 'Specialpages', $query ) . '#Semantic_MediaWiki'
				],
				Message::get( 'specialpages', Message::TEXT, Message::USER_LANGUAGE )
		) );
	}

}
