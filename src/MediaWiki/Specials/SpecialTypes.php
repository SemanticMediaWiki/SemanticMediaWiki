<?php

namespace SMW\MediaWiki\Specials;

use Iterator;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\Navigation\PagerNavigationBuilder;
use MediaWiki\SpecialPage\SpecialPage;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\ErrorValue;
use SMW\DataValues\TypesValue;
use SMW\Formatters\Infolink;
use SMW\Localizer\Message;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\RequestOptions;
use SMW\Settings;
use SMW\Store;
use SMW\TypesRegistry;
use SMW\Utils\HtmlColumns;
use SMW\Utils\HtmlTabs;
use SMW\Utils\Pager;

/**
 * This special page for MediaWiki provides information about available types
 * and those related properties.
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class SpecialTypes extends SpecialPage {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly Settings $settings
	) {
		parent::__construct( 'Types' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ): void {
		$this->setHeaders();
		$out = $this->getOutput();

		$out->addModuleStyles( [
			'ext.smw.styles',
			'ext.smw.page.styles'
		] );
		$out->addModules( 'smw.property.page' );

		$params = Infolink::decodeParameters( $param ?? '', false );
		$typeLabel = reset( $params );

		if ( $typeLabel == false ) {
			$out->setPageTitle( $this->msg( 'types' )->text() );
			$html = $this->getTypesList();
		} else {
			$typeLabel = str_replace( '%', '-', $typeLabel );
			$typeName = str_replace( '_', ' ', $typeLabel );
			$out->setPageTitle( $this->msg( 'smw-types-title', $typeName )->text() );
			$out->prependHTML( $this->getBreadcrumbLink() );
			$html = $this->getPropertiesByType( $typeLabel );
		}

		$out->addHTML( $html );
	}

	/**
	 * @since 3.0
	 *
	 * @param DataValue $dataValue
	 * @param Linker $linker
	 *
	 * @return string
	 */
	public function formatItem( $dataValue, $linker ) {
		// Outdated property? Predefined property definition no longer exists?
		if ( $dataValue->getDataItem()->getInterwiki() === SMW_SQL3_SMWIW_OUTDATED ) {
			$dataItem = $dataValue->getDataItem();

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				new WikiPage( $dataItem->getDBKey(), SMW_NS_PROPERTY ),
				null
			);

			// @phan-suppress-next-line PhanUndeclaredConstantOfClass
			$dataValue->setOption( $dataValue::NO_TEXT_TRANSFORMATION, true );
			// @phan-suppress-next-line PhanUndeclaredConstantOfClass
			$dataValue->setOption( $dataValue::SHORT_FORM, true );

			return $dataValue->getWikiValue();
		}

		$searchlink = Infolink::newBrowsingLink(
			'+', $dataValue->getWikiValue()
		);

		return $dataValue->getLongHTMLText( $linker ) . '&#160;' . $searchlink->getHTML( $linker );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName(): string {
		return 'smw_group/properties-concepts-types';
	}

	private function getTypesList(): string {
		$this->addHelpLink(
			Message::get( "smw-specials-types-helplink", Message::ESCAPED, Message::USER_LANGUAGE ),
			true
		);

		$typeLabels = DataTypeRegistry::getInstance()->getKnownTypeLabels();
		asort( $typeLabels, SORT_STRING );

		$list = $this->makeTypeList( $typeLabels );
		$types = [];

		$htmlColumns = new HtmlColumns();
		$htmlColumns->setColumns( 1 );
		$htmlColumns->addContents( $list, HtmlColumns::INDEXED_LIST );

		$list = Html::rawElement(
			'div',
			[
				'style' => 'margin-top: 1.2em;'
			],
			$htmlColumns->getHtml()
		);

		foreach ( $typeLabels as $id => $label ) {
			$types[] = "<code>$id</code> ($label)";
		}

		$htmlColumns = new HtmlColumns();
		$htmlColumns->setResponsiveCols();
		$htmlColumns->setColumns( 2 );
		$htmlColumns->isRTL( $this->getLanguage()->isRTL() );
		$htmlColumns->addContents( $types, HtmlColumns::PLAIN_LIST );

		$ids = Html::rawElement(
			'div',
			[
				'style' => 'margin-top: 1.2em;'
			],
			$htmlColumns->getHtml()
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'types' );

		$htmlTabs->tab( 'smw-type-list', $this->msg( 'smw-type-tab-types' ) );
		$htmlTabs->content( 'smw-type-list', $list );

		$htmlTabs->tab( 'smw-type-ids', $this->msg( 'smw-type-tab-type-ids' ) );
		$htmlTabs->content( 'smw-type-ids', $ids );

		$html = $htmlTabs->buildHTML(
			[
				'id' => 'smw-types',
				'class' => 'smw-types'
			]
		);

		return Html::rawElement(
			'p',
			[
				'class' => 'plainlinks smw-types-intro'
			],
			$this->msg( 'smw_types_docu' )->parse()
		) . $html;
	}

	private function getPropertiesByType( string|array $typeLabel ) {
		$typeValue = DataValueFactory::getInstance()->newDataValueByType(
			TypesValue::TYPE_ID,
			$typeLabel
		);

		if ( !$typeValue->isValid() ) {
			return Html::rawElement(
				'div',
				[
					'class' => 'plainlinks smw-type-unknown'
				],
				$this->msg( 'smw-special-types-no-such-type', $typeLabel )->escaped()
			);
		}

		$this->addHelpLink( $this->msg( 'smw-specials-bytype-helplink', $typeLabel )->text(), true );

		$pagingLimit = $this->settings->dotGet( 'smwgPagingLimit.type' );

		// not too useful, but we comply to this request
		if ( $pagingLimit <= 0 ) {
			return '';
		}

		$request = $this->getRequest();
		$limit = (int)$request->getVal( 'limit', $pagingLimit );
		$offset = (int)$request->getVal( 'offset', 0 );
		$after = $request->getInt( 'after', 0 );
		$before = $request->getInt( 'before', 0 );

		$cursorMode = self::shouldUseCursorMode( $request->getVal( 'offset', null ) );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;

		if ( $cursorMode ) {
			// PropertySubjectsLookup applies its own LIMIT+1 lookahead in
			// cursor mode, so the caller passes the plain page size.
			$requestOptions->setLimit( $limit );
			$requestOptions->setOption( RequestOptions::CURSOR_MODE, true );
			if ( $after > 0 ) {
				$requestOptions->setCursorAfter( $after );
			} elseif ( $before > 0 ) {
				$requestOptions->setCursorBefore( $before );
			}
		} else {
			// Legacy offset path passes LIMIT+1 (existing convention — the
			// extra row is rendered without trimming).
			$requestOptions->setLimit( $limit + 1 );
			$requestOptions->setOffset( $offset );
		}

		$dataItems = $this->store->getPropertySubjects(
			new Property( '_TYPE' ),
			$typeValue->getDataItem(),
			$requestOptions
		);

		if ( $dataItems instanceof Iterator ) {
			$dataItems = iterator_to_array( $dataItems );
		}

		if ( !$requestOptions->ascending ) {
			$dataItems = array_reverse( $dataItems );
		}

		$typeId = $typeValue->getDataItem()->getFragment();

		$dataValue = DataValueFactory::getInstance()->newDataValueByType(
			$typeId
		);

		$propertyTypeFinder = $this->store->service( 'PropertyTypeFinder' );

		$count = $propertyTypeFinder->countByType(
			$typeId
		);

		$itemCount = Html::rawElement(
			'span',
			[
				'class' => 'item-count'
			],
			$count
		);

		$label = htmlspecialchars( $typeValue->getWikiValue() );
		$typeKey = 'smw-type' . str_replace( '_', '-', strtolower( $typeId ) );
		$msgKey = $this->msg( $typeKey )->exists() ? $typeKey : 'smw-types-default';

		$result = Html::rawElement(
			'div',
			[
				'class' => 'plainlinks smw-types-intro ' . $typeKey
			],
			$this->msg( $msgKey, str_replace( '_', ' ', $label ) )->parse() .
			$this->find_extras( $dataValue, $typeId, $label )
		);

		$count = count( $dataItems );

		if ( $count == 0 ) {
			return $result;
		}

		if ( $cursorMode ) {
			$paginationHtml = $this->renderCursorPager(
				$typeLabel,
				$limit,
				$requestOptions
			);
		} else {
			$paginationHtml = Pager::pagination(
				$this->getTitleFor( 'Types', $typeLabel ),
				$limit,
				$offset,
				$count,
				[ '_target' => '#smw-list' ]
			);
		}

		$html = Html::rawElement(
			'div',
			[
				'class' => 'smw-page-navigation'
			],
			$paginationHtml . Html::rawElement(
				'div',
				[
					'class' => 'smw-page-nav-note'
				],
				$this->msg( 'smw_typearticlecount' )->numParams( min( $limit, $count ) )->text()
			)
		);

		$listBuilder = new ListBuilder(
			$this->store
		);

		$listBuilder->isRTL(
			$this->getLanguage()->isRTL()
		);

		$listBuilder->setItemFormatter( [ $this, 'formatItem' ] );

		$html .= $listBuilder->getColumnList(
			$dataItems
		);

		$errors = $this->find_errors( $dataValue, $typeId, $label );

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'type' );

		$htmlTabs->isRTL(
			$this->getLanguage()->isRTL()
		);

		$htmlTabs->setActiveTab(
			$errors !== null ? 'smw-type-errors' : 'smw-type-list'
		);

		$htmlTabs->tab( 'smw-type-list', $this->msg( 'smw-type-tab-properties' ) . $itemCount );
		$htmlTabs->content( 'smw-type-list', "<div>$html</div>" );

		$htmlTabs->tab(
			'smw-type-errors',
			$this->msg( 'smw-type-tab-errors' ),
			[
				'hide' => $errors === null,
				'class' => 'smw-tab-warning'
			]
		);

		$htmlTabs->content( 'smw-type-errors', "<div>$errors</div>" );

		return $result . $htmlTabs->buildHTML(
			[
				'id' => 'smw-list',
				'class' => 'smw-types'
			]
		);
	}

	/**
	 * Renders the cursor-mode prev/next pager. The legacy offset pager uses
	 * the `_target` convention in `Pager::pagination` to append `#smw-list`
	 * to each generated href so the user lands on the list rather than the
	 * intro text after a page-flip. `PagerNavigationBuilder` has no equivalent
	 * fragment hook, so the fragment is grafted onto each
	 * rendered href here. Only the local hrefs emitted by the nav builder
	 * are affected (they all target `Special:Types/<label>`).
	 *
	 * @since 7.0.0
	 */
	private function renderCursorPager(
		string|array $typeLabel,
		int $limit,
		RequestOptions $cursorOptions
	): string {
		$isFirstPage = !$cursorOptions->hasCursor();

		$navBuilder = new PagerNavigationBuilder( RequestContext::getMain() );
		$navBuilder
			->setPage( $this->getTitleFor( 'Types', $typeLabel ) )
			->setLinkQuery( [ 'limit' => $limit ] )
			->setLimitLinkQueryParam( 'limit' )
			->setCurrentLimit( $limit )
			->setPrevTooltipMsg( 'prevn-title' )
			->setNextTooltipMsg( 'nextn-title' )
			->setLimitTooltipMsg( 'shown-title' );

		$isBackward = $cursorOptions->getCursorBefore() !== null;
		$isAtEnd = !$cursorOptions->getCursorHasMore();
		$showPrev = $isBackward ? !$isAtEnd : true;
		$showNext = $isBackward ? true : !$isAtEnd;

		if ( $showPrev && !$isFirstPage && $cursorOptions->getFirstCursor() !== null ) {
			$navBuilder->setPrevLinkQuery( [ 'before' => (string)$cursorOptions->getFirstCursor() ] );
		}

		if ( $showNext && $cursorOptions->getLastCursor() !== null ) {
			$navBuilder->setNextLinkQuery( [ 'after' => (string)$cursorOptions->getLastCursor() ] );
		}

		$html = $navBuilder->getHtml();

		return preg_replace(
			'/(href="[^"#]*)(")/',
			'$1#smw-list$2',
			$html
		);
	}

	/**
	 * Decides whether the request should be served via the cursor path or
	 * the legacy offset path. Cursor mode is the default; the legacy path
	 * is taken whenever the `offset` URL param is present at all, even if
	 * its value is empty, garbage, or negative. The reasoning: an `offset=`
	 * param signals offset semantics on the client side, so we honour the
	 * intent rather than silently switching modes when the value coerces
	 * to 0.
	 *
	 * Extracted as a static helper so the predicate can be unit-tested
	 * without spinning up the full request context.
	 *
	 * @since 7.0.0
	 *
	 * @param string|null $offsetParamValue The raw value of `?offset=` from
	 *   the request, or null if the param is absent.
	 */
	public static function shouldUseCursorMode( ?string $offsetParamValue ): bool {
		return $offsetParamValue === null;
	}

	private function find_errors( $dataValue, $typeId, string $label ) {
		$errors = [];

		if ( $typeId === '_geo' && $dataValue instanceof ErrorValue ) {
			$errors[] = Html::rawElement(
				'li',
				[],
				$this->msg( 'smw-types-extra-geo-not-available', $label )->parse()
			);
		}

		if ( $errors !== [] ) {
			return Html::rawElement(
				'ul',
				[
					'class' => 'smw-page-content plainlinks'
				],
				implode( '', $errors )
			);
		}
	}

	private function find_extras( $dataValue, $typeId, string $label ): string {
		$html = '';

		if ( $typeId === '_mlt_rec' ) {
			$option = $dataValue->hasFeature( SMW_DV_MLTV_LCODE ) ? 1 : 2;
			$html = '&nbsp;' . $this->msg( 'smw-types-extra-mlt-lcode', $label, $option )->parse();
		}

		$extra = 'smw-type-extra' . str_replace( '_', '-', $typeId );

		if ( $this->msg( $extra )->exists() ) {
			$html = '&nbsp;' . $this->msg( $extra )->parse();
		}

		return $html;
	}

	private function getBreadcrumbLink() {
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
			) .
			Html::rawElement(
				'a',
				[
					'href' => SpecialPage::getTitleFor( 'Types' )->getFullURL()
				],
				$this->msg( 'types' )->escaped()
			)
		);
	}

	/**
	 * @return non-empty-array<list>
	 */
	private function makeTypeList( array $typeLabels ): array {
		$contents = [];
		$linker = smwfGetLinker();

		$groups = TypesRegistry::getTypesByGroup();

		foreach ( $groups as $key => $types ) {

			// Allows to use isset during the match process
			$groups[$key] = array_flip( $types );

			if ( !isset( $contents[$key] ) ) {
				$contents[$key] = [];
			}
		}

		$contents['no-group'] = [];

		foreach ( $typeLabels as $typeId => $label ) {
			$typeValue = TypesValue::newFromTypeId( $typeId );
			$msgKey = 'smw-type' . str_replace( '_', '-', strtolower( $typeId ) );

			if ( Message::exists( $msgKey ) ) {
				$text = $typeValue->getLongWikiText( $linker );
				$text = Message::get( [ $msgKey, $text ], Message::PARSE, Message::USER_LANGUAGE );
			} else {
				$text = $typeValue->getLongHTMLText( $linker );
			}

			$text = Html::rawElement(
				'span',
				[
					'class' => 'plainlinks',
					'style' => 'font-size:85%'
				],
				$text
			);

			foreach ( $groups as $group => $types ) {

				if ( !isset( $types[$typeId] ) ) {
					continue;
				}

				$contents[$group][] = $text;
				unset( $typeLabels[$typeId] );
			}

			if ( isset( $typeLabels[$typeId] ) ) {
				$contents['no-group'][] = $text;
			}
		}

		// Add human readable group label
		foreach ( $contents as $group => $values ) {
			$groupLabel = Message::get( "smw-type-$group", Message::ESCAPED, Message::USER_LANGUAGE );
			$contents[$groupLabel] = $values;
			unset( $contents[$group] );
		}

		return $contents;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( SpecialTypes::class, 'SMWSpecialTypes' );
