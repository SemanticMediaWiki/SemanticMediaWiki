<?php

namespace SMW\MediaWiki\Search;

use SMW;
use SpecialSearch;
use Html;
use Xml;
use Title;
use WikiPage;
use MWNamespace;
use SMW\Message;
use SMW\Store;
use SMW\DIProperty;
use SMW\ProcessingErrorMsgHandler;
use SMW\Highlighter;
use SMW\MediaWiki\Search\Form\FormsBuilder;
use SMW\MediaWiki\Search\Form\FormsFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SearchProfileForm {

	const PROFILE_NAME = 'smw';

	/**
	 * Page that hosts the form/forms definition
	 */
	const FORM_DEFINITION = 'Search-profile-form-definition';

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
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array &$profiles
	 */
	public static function addProfile( $type, array &$profiles ) {

		if ( $type !== 'SMWSearch' ) {
			return;
		}

		$profiles[self::PROFILE_NAME] = array(
			'message' => 'smw-search-profile',
			'tooltip' => 'smw-search-profile-tooltip',
			'namespaces' => \SearchEngine::defaultNamespaces()
		);
	}

	/**
	 * @since 3.0
	 */
	public function setSearchableNamespaces( array $searchableNamespaces ) {
		$this->searchableNamespaces = $searchableNamespaces;
	}

	/**
	 * @since 3.0
	 */
	public function getForm( &$form, $opts ) {

		$hidden = '';
		$html = '';

		foreach ( $opts as $key => $value ) {
			$hidden .= Html::hidden( $key, $value );
		}

		$context = $this->specialSearch->getContext();
		$outputPage = $context->getOutput();

		$outputPage->addModuleStyles( 'ext.smw.special.search.styles' );

		$outputPage->addModules(
			[
				'ext.smw.special.search',
				'ext.smw.tooltip',
				'ext.smw.autocomplete.property'
			]
		);

		$request = $context->getRequest();

		// Set active form
		$this->specialSearch->setExtraParam( 'smw-form', $request->getVal( 'smw-form' )	);

		list( $extendedForm, $formsSelectList, $formNamespaces ) = $this->buildExtendedForm(
			$request
		);

		$sortForm = $this->buildSortForm( $request );

		$options = Html::rawElement(
			'div',
			[
				'class' => 'smw-search-options'
			],
			$sortForm . $formsSelectList
		);

		$searchEngine = $this->specialSearch->getSearchEngine();
		$errors = $this->findErrors( $searchEngine );

		$namespaceForm = $this->buildNamespaceForm(
			$request,
			$searchEngine,
			$formNamespaces
		);

		$query = Html::rawElement(
			'div',
			[
				'id' => 'smw-query',
				'style' => 'display:none;'
			],
			htmlspecialchars( $searchEngine->getQueryString() )
		);

		$form .= Html::rawElement(
			'fieldset',
			[
				'id' => 'smw-searchoptions'
			],
			$hidden . $errors . $query . $options . $extendedForm
		);

		// Different fieldset therefor it is seperate and last!
		$form .= $namespaceForm;
	}

	private function buildNamespaceForm( $request, $searchEngine, $formNamespaces ) {

		$activeNamespaces = array_merge( $this->specialSearch->getNamespaces(), $formNamespaces );

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

		$namespaceForm->setSearchableNamespaces(
			$this->searchableNamespaces
		);

		$namespaceForm->checkNamespaceEditToken(
			$this->specialSearch
		);

		return $namespaceForm->makeFields();
	}

	private function buildExtendedForm( $request ) {

		$title = Title::newFromText( self::FORM_DEFINITION, SMW_NS_RULE );

		if ( !$title->exists() ) {
			return [ '', '', [] ];
		}

		$content = WikiPage::factory( $title )->getContent();

		if ( $content === null ) {
			return [ '', '', [] ];
		}

		$formsBuilder = new FormsBuilder( $request, $this->formsFactory );

		$form = $formsBuilder->buildFromJSON( $content->getNativeData() );
		$parameters = $formsBuilder->getParameters();

		// Set parameters so that any link to a (... 20, 50 ...) list carries
		// those parameters, using them as hidden elements is not sufficient
		foreach ( $parameters as $key => $value ) {
			$this->specialSearch->setExtraParam( $key, $value );
		}

		return [
			$form,
			$formsBuilder->makeSelectList( $title ),
			$formsBuilder->getNsList()
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
			'<li>' . implode( '</li><li>', $list ) . '</li>' . $divider
		);
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

}
