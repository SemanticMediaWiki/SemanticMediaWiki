<?php

namespace SMW\MediaWiki\Page;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Message;
use SMW\MediaWiki\Page\ListBuilder\ItemListBuilder;
use SMW\MediaWiki\Page\ListBuilder\ValueListBuilder;
use SMW\Localizer;
use SMW\PropertyRegistry;
use SMW\RequestOptions;
use SMW\Store;
use SMW\StringCondition;
use SMWDataValue;
use Title;
use SMW\Utils\HtmlTabs;
use SMW\Property\DeclarationExaminerFactory;
use SMW\Utils\JsonView;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyPage extends Page {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DeclarationExaminerFactory
	 */
	private $declarationExaminerFactory;

	/**
	 * @var DIProperty
	 */
	private $property;

	/**
	 * @var SMWDataValue
	 */
	private $propertyValue;

	/**
	 * @var ItemListBuilder
	 */
	private $itemListBuilder;

	/**
	 * @var boolean
	 */
	private $isLockedView = false;

	/**
	 * @var integer
	 */
	private $filterCount = 0;

	/**
	 * @see 3.0
	 *
	 * @param Title $title
	 * @param Store $store
	 * @param DeclarationExaminerFactory $declarationExaminerFactory
	 */
	public function __construct( Title $title, Store $store, DeclarationExaminerFactory $declarationExaminerFactory ) {
		parent::__construct( $title );
		$this->store = $store;
		$this->declarationExaminerFactory = $declarationExaminerFactory;
	}

	/**
	 * @see Page::initParameters()
	 */
	protected function initParameters() {
		// We use a smaller limit here; property pages might become large
		$this->limit = $this->getOption( 'pagingLimit' );
		$this->property = DIProperty::newFromUserLabel( $this->getTitle()->getText() );
		$this->propertyValue = DataValueFactory::getInstance()->newDataValueByItem( $this->property );
	}

	/**
	 * @see Page::initHtml
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	protected function initHtml() {

		$redirectTarget = $this->store->getRedirectTarget( $this->property );

		if ( !$redirectTarget->equals( $this->property ) ) {
			return '';
		}

		$declarationExaminer = $this->declarationExaminerFactory->newDeclarationExaminer(
			$this->store,
			$this->fetchSemanticDataFromEditInfo()
		);

		$declarationExaminer->check( $this->property );
		$this->isLockedView = $declarationExaminer->isLocked();

		$declarationExaminerMsgBuilder = $this->declarationExaminerFactory->newDeclarationExaminerMsgBuilder();

		return $declarationExaminerMsgBuilder->buildHTML( $declarationExaminer );
	}

	/**
	 * @see Page::isLockedView
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	protected function isLockedView() {
		return $this->isLockedView;
	}

	/**
	 * @see Page::getRedirectTargetURL
	 *
	 * @since 3.0
	 *
	 * @return string|boolean
	 */
	protected function getRedirectTargetURL() {

		$label = $this->getTitle()->getText();

		if ( ( $key = PropertyRegistry::getInstance()->findPropertyIdByLabel( $label ) ) === false ) {
			return false;
		}

		$property = new DIProperty(
			$key
		);

		// Ensure to redirect to `Property:Modification date` and not using
		// a possible user contextualized version such as `Property:Date de modification`
		$canonicalLabel = $property->getCanonicalLabel();

		if ( $canonicalLabel !== '' && $label !== $canonicalLabel ) {
			return $property->getCanonicalDiWikiPage()->getTitle()->getFullURL();
		}

		return false;
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected function getHtml() {

		if ( !$this->store->getRedirectTarget( $this->property )->equals( $this->property ) ) {
			return '';
		}

		$context = $this->getContext();
		$language = $context->getLanguage();

		$matches = [];

		$context->getOutput()->addModuleStyles( [ 'ext.smw.style', 'ext.smw.page.styles' ] );
		$context->getOutput()->addModules( [ 'smw.property.page', 'smw.jsonview' ] );

		$context->getOutput()->setPageTitle(
			$this->propertyValue->getFormattedLabel( DataValueFormatter::WIKI_LONG )
		);

		$this->itemListBuilder = new ItemListBuilder(
			$this->store
		);

		$this->itemListBuilder->isRTL(
			$language->isRTL()
		);

		$this->itemListBuilder->setLanguageCode(
			$language->getCode()
		);

		$this->itemListBuilder->isUserDefined(
			$this->property->isUserDefined()
		);

		if ( $this->mParserOutput instanceof \ParserOutput ) {
			preg_match_all(
				"/" . "<section class=\"smw-property-specification\"(.*)?>([\s\S]*?)<\/section>" . "/m",
				$this->mParserOutput->getText(),
				$matches
			);
		}

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'property' );

		$htmlTabs->isRTL(
			$language->isRTL()
		);

		$html = $this->makeValueList();
		$isFirst = $html === '';

		$htmlTabs->tab( 'smw-property-value', $this->msg( 'smw-property-tab-usage' ) . $this->getCount(), [ 'hide' => $html === '' ] );
		$htmlTabs->content( 'smw-property-value', $html );

		// Redirects
		[ $html, $itemCount ] = $this->makeItemList( 'redirect', '_REDI', true );
		$isFirst = $isFirst && $html === '';

		$htmlTabs->tab( 'smw-property-redi', $this->msg( 'smw-property-tab-redirects' ) . $itemCount, [ 'hide' => $html === '' ] );
		$htmlTabs->content( 'smw-property-redi', $html );

		// Subproperties
		[ $html, $itemCount ] = $this->makeItemList( 'subproperty', '_SUBP', true );
		$isFirst = $isFirst && $html === '';

		$htmlTabs->tab( 'smw-property-subp', $this->msg( 'smw-property-tab-subproperties' ) . $itemCount, [ 'hide' => $html === '' ] );
		$htmlTabs->content( 'smw-property-subp', $html );

		// Improperty values
		[ $html, $itemCount ] = $this->makeItemList( 'error', '_ERRP', false );
		$isFirst = $isFirst && $html === '';

		$htmlTabs->tab( 'smw-property-errp', $this->msg( 'smw-property-tab-errors' ) . $itemCount, [
				'hide' => $html === '',
				'class' => 'smw-tab-warning'
			]
		);

		$htmlTabs->content( 'smw-property-errp', $html );

		// Constraint schema
		$applicationFactory = ApplicationFactory::getInstance();

		$constraintSchemaCompiler = $applicationFactory->create( 'ConstraintFactory' )->newConstraintSchemaCompiler(
			$this->store
		);

		$data = $constraintSchemaCompiler->compileConstraintSchema(
			$this->property
		);

		if ( $data !== [] ) {
			$constraint = ( new JsonView() )->create(
				'constraint',
				json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				2
			);

			$attr = [
				'title' => $this->msg( 'smw-property-tab-constraint-schema-title' )
			];

			$htmlTabs->tab( 'smw-property-constraint', $this->msg( 'smw-property-tab-constraint-schema' ), $attr );
			$htmlTabs->content( 'smw-property-constraint', $constraint );
		}

		$schemaFinder = $applicationFactory->singleton( 'SchemaFactory' )->newSchemaFinder();

		$schemaList = $schemaFinder->newSchemaList(
			$this->property,
			new DIProperty( '_PROFILE_SCHEMA' )
		);

		$data = $schemaList->toArray();

		if ( $data !== [] ) {
			$profile = ( new JsonView() )->create(
				'profile',
				json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				2
			);

			$htmlTabs->tab( 'smw-property-profile', $this->msg( 'smw-property-tab-profile-schema' ) );
			$htmlTabs->content( 'smw-property-profile', $profile );
		}

		// ... more
		if ( isset( $matches[2] ) && $matches[2] !== [] ) {
			$html = "<div>" . implode( '</div><div>', $matches[2] ) . "</div>";
		} else {
			$html = '';
		}

		$htmlTabs->tab(
			'smw-property-spec',
			$this->msg( 'smw-property-tab-specification' ),
			[
				'hide' => $html === '',
				'class' => $isFirst ? 'smw-tab-spec' : 'smw-tab-spec smw-tab-right'
			]
		);

		$htmlTabs->content( 'smw-property-spec', $html );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-property clearfix' ]
		);

		return $html;
	}

	private function fetchSemanticDataFromEditInfo() {

		$applicationFactory = ApplicationFactory::getInstance();

		$editInfo = $applicationFactory->newMwCollaboratorFactory()->newEditInfo(
			$this->getPage()
		);

		return $editInfo->fetchSemanticData();
	}

	private function makeItemList( $key, $propertyKey, $checkProperty = true ) {

		// Ignore the list when a filter is present
		if ( $this->getContext()->getRequest()->getVal( 'filter', '' ) !== '' ) {
			return [ '', '' ];
		}

		$propertyListLimit = $this->getOption( 'smwgPropertyListLimit' );
		$listLimit = $propertyListLimit[$key];

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = true;

		// +1 look ahead
		$requestOptions->setLimit(
			$listLimit + 1
		);

		$this->itemListBuilder->setListLimit(
			$listLimit
		);

		$this->itemListBuilder->setListHeader(
			'smw-propertylist-' . $key
		);

		$this->itemListBuilder->checkProperty(
			$checkProperty
		);

		$html = $this->itemListBuilder->buildHTML(
			new DIProperty( $propertyKey ),
			$this->getDataItem(),
			$requestOptions
		);

		$itemCount = Html::rawElement(
			'span',
			[
				'class' => 'item-count'
			],
			$this->itemListBuilder->getItemCount()
		);

		return [ $html, $itemCount ];
	}

	private function makeValueList() {

		$request = $this->getContext()->getRequest();
		$language = $this->getContext()->getLanguage();

		$valueListBuilder = new ValueListBuilder(
			$this->store
		);

		$valueListBuilder->isRTL(
			$language->isRTL()
		);

		$valueListBuilder->setLanguageCode(
			$language->getCode()
		);

		$valueListBuilder->setPagingLimit(
			$this->getOption( 'pagingLimit' )
		);

		$valueListBuilder->setMaxPropertyValues(
			$this->getOption( 'smwgMaxPropertyValues' )
		);

		$valueListBuilder->applyLocalTimeOffset(
			Localizer::getInstance()->hasLocalTimeOffsetPreference( $this->getPage()->getUser() )
		);

		$html = $valueListBuilder->createHtml(

			$this->property,
			$this->getDataItem(),
			[
				'limit'  => $request->getVal( 'limit', $this->getOption( 'pagingLimit' ) ),
				'offset' => $request->getVal( 'offset', '0' ),
				'from'   => $request->getVal( 'from', '' ),
				'until'  => $request->getVal( 'until', '' ),
				'filter' => $request->getVal( 'filter', '' )
			]
		);

		$this->filterCount = $valueListBuilder->getFilterCount();

		return $html;
	}

	private function msg( $params, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $params, $type, $lang );
	}

	private function getCount() {

		if ( $this->filterCount !== null ) {
			return Html::rawElement(
				'span',
				[
					'title' => $this->msg( 'smw-filter-count' ),
					'class' => 'usage-count'
				],
				$this->filterCount
			);
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );

		// Label that corresponds to the display and sort characteristics
		if ( $this->property->isUserDefined() ) {
			$searchLabel = $this->propertyValue->getSearchLabel();
		} else {
			$searchLabel = $this->property->getKey();
			$requestOptions->setOption( RequestOptions::SEARCH_FIELD, 'smw_title' );
		}

		$requestOptions->addStringCondition( $searchLabel, StringCondition::COND_EQ );

		$cachedLookupList = $this->store->getPropertiesSpecial( $requestOptions );
		$usageList = $cachedLookupList->fetchList();

		if ( !$usageList || $usageList === [] ) {
			return '';
		}

		$usage = end( $usageList );
		$usageCount = $usage[1];
		$date = $this->getContext()->getLanguage()->timeanddate( $cachedLookupList->getTimestamp() );

		$countMsg = Message::get( [ 'smw-property-indicator-last-count-update', $date ] );
		$indicatorClass = ( $usageCount < 25000 ? ( $usageCount > 5000 ? ' moderate' : '' ) : ' high' );

		return Html::rawElement(
			'span',
			[
				'title' => $countMsg,
				'class' => 'usage-count' . $indicatorClass
			],
			$usageCount
		);
	}

}
