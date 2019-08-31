<?php

namespace SMW\MediaWiki\Search\ProfileForm;

use Html;
use MWNamespace;
use SMW;
use SMW\Schema\SchemaFactory;
use SMW\ProcessingErrorMsgHandler;
use SMW\Utils\HtmlModal;
use SMW\Store;
use SMW\Message;
use SpecialSearch;
use Title;
use WikiPage;
use Xml;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProfileForm {

	const PROFILE_NAME = 'smw';

	/**
	 * Page that hosts the form/forms definition
	 */
	const SCHEMA_TYPE = 'SEARCH_FORM_SCHEMA';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SpecialSearch
	 */
	private $specialSearch;

	/**
	 * @var FormsFactory
	 */
	private $formsFactory;

	/**
	 * @var []
	 */
	private $searchableNamespaces = [];

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param SpecialSearch $specialSearch
	 */
	public function __construct( Store $store, SpecialSearch $specialSearch ) {
		$this->store = $store;
		$this->specialSearch = $specialSearch;
		$this->formsFactory = new FormsFactory();
	}

	/**
	 * @since 3.1
	 *
	 * @param string $profile
	 *
	 * @return boolean
	 */
	public static function isValidProfile( $profile ) {
		return $profile === ProfileForm::PROFILE_NAME;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array &$profiles
	 */
	public static function addProfile( $type, array &$profiles, array $options ) {

		if ( $type !== SMW_SPECIAL_SEARCHTYPE ) {
			return;
		}

		$profiles[self::PROFILE_NAME] = [
			'message' => 'smw-search-profile',
			'tooltip' => 'smw-search-profile-tooltip',
			'namespaces' => $options['default_namespaces']
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return array
	 */
	public static function getFormDefinitions( Store $store ) {

		static $data = null;

		if ( $data !== null ) {
			return $data;
		}

		$schemaFactory = new SchemaFactory();

		$schemaFinder = $schemaFactory->newSchemaFinder(
			$store
		);

		$schemaList = $schemaFinder->getSchemaListByType(
			self::SCHEMA_TYPE
		);

		return $data = $schemaList->merge( $schemaList );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function getPrefixMap( array $data ) {

		$map = [];

		if (
			isset( $data['term_parser']['prefix'] ) &&
			$data['term_parser']['prefix'] ) {
			$map = (array)$data['term_parser']['prefix'];
		}

		return $map;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $searchableNamespaces
	 */
	public function setSearchableNamespaces( array $searchableNamespaces ) {
		$this->searchableNamespaces = $searchableNamespaces;
	}

	/**
	 * @since 3.0
	 *
	 * @param string &$form
	 * @param array $opts
	 */
	public function buildForm( &$form, array $opts = [] ) {

		$hidden = '';
		$html = '';

		$context = $this->specialSearch->getContext();
		$request = $context->getRequest();

		foreach ( $opts as $key => $value ) {
			$hidden .= Html::hidden( $key, $value );
		}

		$outputPage = $context->getOutput();

		$outputPage->addModuleStyles( [ 'smw.ui.styles', 'smw.special.search.styles' ] );
		$outputPage->addModules(
			[
				'smw.ui',
				'smw.special.search',
				'ext.smw.tooltip',
				'ext.smw.autocomplete.property'
			]
		);

		// Set active form
		$this->specialSearch->setExtraParam( 'smw-form', $request->getVal( 'smw-form' )	);

		$searchEngine = $this->specialSearch->getSearchEngine();

		if ( ( $queryLink = $searchEngine->getQueryLink() ) instanceof \SMWInfolink ) {
			$queryLink->setCaption( $this->msg( 'smw-search-profile-link-caption-query', Message::TEXT ) );
			$queryLink->setLinkAttributes(
				[
					'title' => 'Special:Ask'
				]
			);
		}

		list( $searchForms, $formList, $termPrefixes, $preselectNamespaces, $hiddenNamespaces ) = $this->buildSearchForms(
			$request
		);

		$sortForm = $this->buildSortForm( $request );

		$namespaceForm = $this->buildNamespaceForm(
			$request,
			$searchEngine,
			$preselectNamespaces,
			$hiddenNamespaces,
			$hidden
		);

		$options = Html::rawElement(
			'div',
			[
				'class' => 'smw-search-options'
			],
			Html::rawElement(
				'div',
				[
					'style' => 'color: #586069;position: relative;display: inline-block; padding-top: 5px; padding-bottom: 2px;'
				],
				''
			) . $sortForm . $formList . HtmlModal::link(
				'<span class="smw-icon-info"></span>',
				[
					'data-id' => 'smw-search-profile-extended-cheat-sheet'
				]
			)
		);

		$errors = $this->findErrors( $searchEngine );

		$modal = HtmlModal::modal(
			Message::get( 'smw-cheat-sheet', Message::TEXT, Message::USER_LANGUAGE ),
			$this->profile_sheet( $searchEngine->getQueryString(), $queryLink, $termPrefixes ),
			[
				'id' => 'smw-search-profile-extended-cheat-sheet',
				'class' => 'plainlinks',
				'style' => 'display:none;'
			]
		);

		$form .= Html::rawElement(
			'fieldset',
			[
				'id' => 'smw-searchoptions'
			],
			$hidden . $errors . $modal . $options . $searchForms
		);

		// Different fieldset therefore it is used as last element
		$form .= $namespaceForm;
	}

	private function buildNamespaceForm( $request, $searchEngine, $preselectNamespaces, $hiddenNamespaces, &$hidden ) {

		$activeNamespaces = array_merge( $this->specialSearch->getNamespaces(), $preselectNamespaces );
		$default = false;

		$data = $this->getFormDefinitions( $this->store );

		foreach ( $this->searchableNamespaces as $ns => $name ) {

			if ( $request->getCheck( 'ns' . $ns ) ) {
				$activeNamespaces[] = $ns;
				$this->specialSearch->setExtraParam( 'ns' . $ns, true );
			}
		}

		if ( $searchEngine !== null ) {
			$searchEngine->setNamespaces( $activeNamespaces );
		}

		// Contains the copied Advanced namespace form
		$namespaceForm = $this->formsFactory->newNamespaceForm();

		$namespaceForm->setActiveNamespaces(
			$activeNamespaces
		);

		$namespaceForm->setHiddenNamespaces(
			$hiddenNamespaces
		);

		if ( isset( $data['namespaces']['default_hide'] ) ) {
			$default = $data['namespaces']['default_hide'];
		}

		$namespaceForm->setHideList(
			$request->getVal( 'ns-list', $default )
		);

		$namespaceForm->setSearchableNamespaces(
			$this->searchableNamespaces
		);

		$namespaceForm->checkNamespaceEditToken(
			$this->specialSearch
		);

		// Carry over the status (hide/show) of the ns section during a search
		// request so we don't have to set a cookie while still being able to
		// retain its status on whether the users has the NS hidden or not.
		$hidden .= Html::hidden( 'ns-list', $request->getVal( 'ns-list', $default ) );

		return $namespaceForm->makeFields();
	}

	private function buildSearchForms( $request ) {

		$data = $this->getFormDefinitions( $this->store );

		if ( $data === [] ) {
			return [ '', '', [], [], [] ];
		}

		$formsBuilder = new FormsBuilder( $request, $this->formsFactory );

		$form = $formsBuilder->buildForm( $data );
		$parameters = $formsBuilder->getParameters();

		// Set parameters so that any link to a (... 20, 50 ...) list carries
		// those parameters, using them as hidden elements is not sufficient
		foreach ( $parameters as $key => $value ) {
			$this->specialSearch->setExtraParam( $key, $value );
		}

		$formList = $formsBuilder->buildFormList();

		return [
			$form,
			$formList,
			$formsBuilder->getTermPrefixes(),
			$formsBuilder->getPreselectNsList(),
			$formsBuilder->getHiddenNsList()
		];
	}

	private function findErrors( $searchEngine ) {

		if ( ( $errors = $searchEngine->getErrors() ) === [] ) {
			return '';
		}

		$divider = "<div class='divider'></div>";

		$list = ProcessingErrorMsgHandler::normalizeAndDecodeMessages(
			$errors
		);

		return Html::rawElement(
			'ul',
			[
				'class' => 'smw-errors',
				'style' => 'color:#b32424;'
			],
			'<li>' . implode( '</li><li>', $list ) . '</li>'
		) . $divider;
	}

	private function buildSortForm( $request ) {

		$sortForm = $this->formsFactory->newSortForm( $request );

		// TODO this information should come from the store and not being
		// derived from a class! How should such characteristic be represented?
		$features = [
			'best' => is_a( $this->store, "SMWElasticStore" )
		];

		$form = $sortForm->makeFields( $features );
		$parameters = $sortForm->getParameters();

		foreach ( $parameters as $key => $value ) {
			$this->specialSearch->setExtraParam( $key, $value );
		}

		return $form;
	}

	private function profile_sheet( $query, $queryLink, $termPrefixes ) {

		$text = Message::get( 'smw-search-profile-extended-help-intro', Message::PARSE, Message::USER_LANGUAGE );

		$link = $queryLink !== null ? $queryLink->getHtml() : '';

		if ( $link !== '' ) {
			$text .= $this->section( 'smw-search-profile-extended-section-query' );
			$text .= Html::rawElement( 'pre', [], $query ) . '&nbsp;';
			$text .= $this->msg( [ 'smw-search-profile-extended-help-query-link', $link ], Message::TEXT );
		}

		$text .= $this->section( 'smw-search-profile-extended-section-search-syntax' );
		$text .= $this->msg( 'smw-search-profile-extended-help-search-syntax', Message::TEXT );

		$syntax = $this->msg( 'smw-search-profile-extended-help-search-syntax-simplified-in' );
		$syntax .= $this->msg( 'smw-search-profile-extended-help-search-syntax-simplified-phrase' );
		$syntax .= $this->msg( 'smw-search-profile-extended-help-search-syntax-simplified-has' );
		$syntax .= $this->msg( 'smw-search-profile-extended-help-search-syntax-simplified-not' );

		if ( $termPrefixes !== [] ) {
			$prefixes = '';

			foreach ( array_keys( $termPrefixes ) as $pref ) {
				$prefixes .= ( $prefixes === '' ? '' : ', ' ) . "<code>$pref:</code>";
			}

			$syntax .= $this->msg( [ 'smw-search-profile-extended-help-search-syntax-prefix', $prefixes ] );
		}

		$syntax .= $this->msg( [ 'smw-search-profile-extended-help-search-syntax-reserved', "'&&', 'AND', '||', 'OR', '(', ')', '[[', ']]'" ] );

		$text .= Html::rawElement( 'div', [ 'id' => 'smw-search-synatx-list' ],
			$syntax
		);

		$text .= Html::rawElement( 'p', [] ,
			$this->msg( 'smw-search-profile-extended-help-search-syntax-note' )
		);

		$text .= $this->section( 'smw-search-profile-extended-section-sort' );
		$text .= $this->msg( 'smw-search-profile-extended-help-sort' );
		$sort = $this->msg( 'smw-search-profile-extended-help-sort-title' );
		$sort .= $this->msg( 'smw-search-profile-extended-help-sort-recent' );

		if ( is_a( $this->store, "SMWElasticStore" ) ) {
			$sort .= $this->msg( 'smw-search-profile-extended-help-sort-best' );
		}

		$text .= Html::rawElement( 'div', [ 'id' => 'smw-search-sort-list' ],
			$sort
		);

		$formLink = Html::element(
			'a',
			[
				'href' => Title::newFromText( 'Special:SearchByProperty/Schema type/' . self::SCHEMA_TYPE )->getFullUrl()
			],
			$this->msg( 'smw-search-profile-extended-help-find-forms' )
		);

		$text .= $this->section( 'smw-search-profile-extended-section-form' );
		$text .= $this->msg( [ 'smw-search-profile-extended-help-form', $formLink ], Message::TEXT );
		$text .= $this->section( 'smw-search-profile-extended-section-namespace' );
		$text .= $this->msg( 'smw-search-profile-extended-help-namespace' );

		return $text;
	}

	private function section( $msg, $attributes = [] ) {
		return Html::rawElement(
			'div',
			[
				'class' => 'smw-text-strike',
				'style' => 'padding: 5px 0 5px 0;'
			],
			Html::rawElement(
				'span',
				[
					'style' => 'font-size: 1.2em; margin-left:0px'
				],
				Message::get( $msg, Message::TEXT, Message::USER_LANGUAGE )
			)
		);
	}

	private function msg( $msg, $type = Message::PARSE, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $msg, $type, $lang );
	}

}
