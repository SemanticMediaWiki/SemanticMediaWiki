<?php

use SMW\ApplicationFactory;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\Utils\HtmlTabs;
use SMW\Utils\Pager;
use SMW\Utils\HtmlColumns;
use SMWDataItem as DataItem;
use SMW\MediaWiki\Collator;
use SMW\TypesRegistry;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMWInfolink as Infolink;
use SMW\DataValues\TypesValue;
use SMWErrorValue as ErrorValue;
use SMW\MediaWiki\Page\ListBuilder;
use SMW\Message;

/**
 * This special page for MediaWiki provides information about available types
 * and those related properties.
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class SMWSpecialTypes extends SpecialPage {

	/**
	 * @see SpecialPage::execute
	 */
	public function __construct() {
		parent::__construct( 'Types' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $param ) {
		$this->setHeaders();
		$out = $this->getOutput();

		$out->addModuleStyles( 'ext.smw.page.styles' );
		$out->addModules( 'smw.property.page' );

		$params = Infolink::decodeParameters( $param, false );
		$typeLabel = reset( $params );

		if ( $typeLabel == false ) {
			$out->setPageTitle( wfMessage( 'types' )->text() );
			$html = $this->getTypesList();
		} else {
			$typeLabel = str_replace( '%', '-', $typeLabel );
			$typeName = str_replace( '_', ' ', $typeLabel );
			$out->setPageTitle( wfMessage( 'smw-types-title', $typeName )->text() );
			$out->prependHTML( $this->getBreadcrumbLink() );
			$html = $this->getPropertiesByType( $typeLabel );
		}

		$out->addHTML( $html );
	}

	/**
	 * @since 3.0
	 *
	 * @param DataValue $dataValue
	 * @param Link $linker
	 *
	 * @return string
	 */
	public function formatItem( $dataValue, $linker ) {

		// Outdated property? Predefined property definition no longer exists?
		if ( $dataValue->getDataItem()->getInterwiki() === SMW_SQL3_SMWIW_OUTDATED ) {
			$dataItem = $dataValue->getDataItem();

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				new DIWikiPage( $dataItem->getDBKey(), SMW_NS_PROPERTY ),
				null
			);

			$dataValue->setOption( $dataValue::NO_TEXT_TRANSFORMATION, true );
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
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/properties-concepts-types';
	}

	private function getTypesList() {

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
			wfMessage( 'smw_types_docu' )->parse()
		) . $html;
	}

	private function getPropertiesByType( $typeLabel ) {

		$typeValue = DataValueFactory::getInstance()->newTypeIDValue(
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

		$this->addHelpLink( wfMessage( 'smw-specials-bytype-helplink', $typeLabel )->escaped(), true );
		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore();

		$pagingLimit = $applicationFactory->getSettings()->dotGet( 'smwgPagingLimit.type' );

		// not too useful, but we comply to this request
		if ( $pagingLimit <= 0 ) {
			return '';
		}

		$limit = $this->getRequest()->getVal( 'limit', $pagingLimit );
		$offset = $this->getRequest()->getVal( 'offset', 0 );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( $limit + 1 );
		$requestOptions->setOffset( $offset );

		$dataItems = $store->getPropertySubjects(
			new DIProperty( '_TYPE' ),
			$typeValue->getDataItem(),
			$requestOptions
		);

		if ( $dataItems instanceof \Iterator ) {
			$dataItems = iterator_to_array( $dataItems );
		}

		if ( !$requestOptions->ascending ) {
			$dataItems = array_reverse( $dataItems );
		}

		$typeId = $typeValue->getDataItem()->getFragment();

		$dataValue = DataValueFactory::getInstance()->newTypeIDValue(
			$typeId
		);

		$propertyTypeFinder = $store->service( 'PropertyTypeFinder' );

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
		$msgKey = wfMessage( $typeKey )->exists() ? $typeKey : 'smw-types-default';

		$result = \Html::rawElement(
			'div',
			[
				'class' => 'plainlinks smw-types-intro '. $typeKey
			],
			wfMessage( $msgKey, str_replace( '_', ' ', $label ) )->parse() .
			$this->find_extras( $dataValue, $typeId, $label )
		);

		$count = count( $dataItems );

		if ( $count == 0 ) {
			return $result;
		}

		$html = Html::rawElement(
			'div' ,
			[
				'class' => 'smw-page-navigation'
			],
			Pager::pagination(
				$this->getTitleFor( 'Types', $typeLabel ),
				$limit,
				$offset,
				$count,
				[ '_target' => '#smw-list' ]
			) . Html::rawElement(
				'div' ,
				[
					'class' => 'smw-page-nav-note'
				],
				wfMessage( 'smw_typearticlecount' )->numParams( min( $limit, $count ) )->text()
			)
		);

		$listBuilder = new ListBuilder(
			$store
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
			$errors !==  null ? 'smw-type-errors' : 'smw-type-list'
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

	private function find_errors( $dataValue, $typeId, $label ) {

		$errors = [];

		if ( $typeId === '_geo' && $dataValue instanceof ErrorValue ) {
			$errors[] = Html::rawElement(
				'li',
				[],
				wfMessage( 'smw-types-extra-geo-not-available', $label )->parse()
			);
		}

		if ( $errors !== [] ) {
			return Html::rawElement(
				'ul',
				[
					'class' => 'smw-page-content plainlinks'
				],
				implode( '', $errors)
			);
		}
	}

	private function find_extras( $dataValue, $typeId, $label ) {

		$html = '';

		if ( $typeId === '_mlt_rec' ) {
			$option = $dataValue->hasFeature( SMW_DV_MLTV_LCODE ) ? 1 : 2;
			$html = '&nbsp;' . wfMessage( 'smw-types-extra-mlt-lcode', $label, $option )->parse();
		}

		$extra = 'smw-type-extra' . str_replace( '_', '-', $typeId );

		if ( wfMessage( $extra )->exists() ) {
			$html = '&nbsp;' . wfMessage( $extra )->parse();
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
					'href' => SpecialPage::getTitleFor( 'Types')->getFullURL()
				],
				$this->msg( 'types' )->escaped()
			)
		);
	}

	private function makeTypeList( $typeLabels ) {

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

			$text = $typeValue->getLongHTMLText( $linker );

			if ( Message::exists( $msgKey ) ) {
				$msg = Message::get( [ $msgKey, '' ], Message::PARSE, Message::USER_LANGUAGE );
				$text .= Html::rawElement(
					'span' ,
					[
						'class' => 'plainlinks',
						'style' => 'font-size:85%'
					],

					// Remove the first two chars which are a localized
					// diacritical, quotation mark
					str_replace( mb_substr( $msg, 0, 2 ), '', $msg )
				);
			}

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
			$groupLabel = Message::get( "smw-type-$group", Message::TEXT, Message::USER_LANGUAGE );
			$contents[$groupLabel] = $values;
			unset( $contents[$group] );
		}

		return $contents;
	}

}
