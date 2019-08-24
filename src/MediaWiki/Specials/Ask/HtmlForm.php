<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use Title;
use SMWQueryResult as QueryResult;
use SMW\Utils\HtmlTabs;
use SMW\Utils\UrlArgs;
use SMW\Query\QueryLinker;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class HtmlForm {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var Query
	 */
	private $query;

	/**
	 * @var array
	 */
	private $callbacks = [];

	/**
	 * @var boolean
	 */
	private $isEditMode = true;

	/**
	 * @var boolean
	 */
	private $isBorrowedMode = false;

	/**
	 * @var boolean
	 */
	private $isPostSubmit = false;

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		$this->title = $title;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 */
	public function setParameters( array $parameters ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $queryString
	 */
	public function setQueryString( $queryString ) {
		$this->queryString = $queryString;
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 */
	public function setQuery( Query $query = null ) {
		$this->query = $query;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $callbacks
	 */
	public function setCallbacks( array $callbacks ) {
		$this->callbacks = $callbacks;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isEditMode
	 */
	public function isEditMode( $isEditMode ) {
		$this->isEditMode = (bool)$isEditMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isBorrowedMode
	 */
	public function isBorrowedMode( $isBorrowedMode ) {
		$this->isBorrowedMode = (bool)$isBorrowedMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isPostSubmit
	 */
	public function isPostSubmit( $isPostSubmit ) {
		$this->isPostSubmit = (bool)$isPostSubmit;
	}

	/**
	 * @since 3.0
	 *
	 * @param UrlArgs $urlArgs
	 * @param QueryResult|string|null $queryResult
	 *
	 * @return string
	 */
	public function getForm( UrlArgs $urlArgs, $queryResult = null, array $queryLog = [] ) {

		$html = $this->buildHTML( $urlArgs, $queryResult, $queryLog );

		if ( $this->isPostSubmit ) {
			$params = [
				'action' => $this->title->getLocalUrl( '#search' ),
				'name' => 'ask',
				'method' => 'post'
			];
		} else {
			$params = [
 				'action' => $GLOBALS['wgScript'],
 				'name' => 'ask',
 				'method' => 'get'
			];
		}

		return Html::rawElement( 'form', $params, $html );
	}

	private function buildHTML( $urlArgs, $queryResult, $queryLog ) {

		$navigation = '';
		$queryLink = null;
		$isFromCache = false;
		$infoText = '';

		if ( $queryLog !== [] ) {
			$infoText = '<h3>' . wfMessage( 'smw-ask-extra-query-log' )->text() . '</h3>';
			$infoText .= '<pre>' . json_encode( $queryLog, JSON_PRETTY_PRINT ) . '</pre>';
		}

		if ( $queryResult instanceof QueryResult ) {
			$navigation = NavigationLinksWidget::navigationLinks(
				$this->title,
				$urlArgs,
				$queryResult->getCount(),
				$queryResult->hasFurtherResults()
			);

			$isFromCache = $queryResult->isFromCache();

			if ( $this->query !== null ) {
				$queryLink = QueryLinker::get( $this->query, $this->parameters );
			} elseif ( ( $query = $queryResult->getQuery() ) !== null ) {
				$queryLink = QueryLinker::get( $query, $this->parameters );
			}
		}

		$html = '';
		$hideForm = false;
		$urlArgs->set( 'eq', 'yes' );

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'ask' );
		$htmlTabs->setActiveTab( 'smw-askt-result' );

		if ( $this->isEditMode ) {
			$html = $this->editElements( $urlArgs );
			$hideForm = true;
		}

		$isEmpty = $queryLink === null;
		$editLink = $this->title->getLocalURL( $urlArgs );

		// Submit
		$html .= LinksWidget::resultSubmitLink(
			$hideForm
		);

		if ( !$this->isEditMode && !$isEmpty ) {
			$htmlTabs->tab(
				'smw-askt-edit',
				LinksWidget::editLink( $editLink ),
				[
					'hide' => $this->isBorrowedMode,
					'class' => 'edit-action'
				]
			);
		} elseif ( !$isEmpty ) {
			$htmlTabs->tab(
				'smw-askt-compact',
				LinksWidget::hideLink( $editLink ),
				[
					'hide' => $this->isBorrowedMode,
					'class' => 'compact-action'
				]
			);
		}

		$htmlTabs->tab(
			'smw-askt-result',
			wfMessage( 'smw-ask-tab-result' )->text(),
			[
				'hide' => $isEmpty,
				'class' => $isFromCache ? ' result-cache' : ''
			]
		);

		$links = [];

		$htmlTabs->tab(
			'smw-askt-code',
			wfMessage( 'smw-ask-tab-code' )->text(),
			[
				'hide' => $this->isBorrowedMode || $isEmpty
			]
		);

		$code = '';

		if ( isset( $this->callbacks['code_handler'] ) && is_callable( $this->callbacks['code_handler'] ) ) {
			$code = $this->callbacks['code_handler']();
		}

		$htmlTabs->content(
			'smw-askt-code',
			'<div style="margin-top:15px; margin-bottom:15px;">' .
			LinksWidget::embeddedCodeBlock(	$code, true ) . '</div>'
		);

		if ( !isset( $this->parameters['source'] ) || $this->parameters['source'] === '' ) {
			$debugLink = LinksWidget::debugLink( $this->title, $urlArgs, $isEmpty, true );

			$htmlTabs->tab(
				'smw-askt-debug',
				$debugLink,
				[
					'hide' => $debugLink === '' || !$this->isEditMode ,
					'class' => 'smw-tab-right'
				]
			);
		}

		if ( isset( $this->callbacks['borrowed_msg_handler'] ) && is_callable( $this->callbacks['borrowed_msg_handler'] ) ) {
			$this->callbacks['borrowed_msg_handler']( $links, $infoText );
		}

		$basicLinks = NavigationLinksWidget::basicLinks(
			$navigation,
			$queryLink
		);

		$htmlTabs->content( 'smw-askt-result', $basicLinks );

		if ( !$isEmpty ) {
			$htmlTabs->tab(
				'smw-askt-extra',
				wfMessage( 'smw-ask-tab-extra' )->text(),
				[
					'class' => 'smw-tab-right'
				]
			);

			if ( is_array( $links ) ) {

				// External source cannot disable the cache
				if ( isset( $this->parameters['source'] ) && $this->parameters['source'] !== '' ) {
					$isFromCache = false;
				}

				if ( ( $noCacheLink = LinksWidget::noQCacheLink( $this->title, $urlArgs, $isFromCache ) ) !== '' ) {
					$links[] = $noCacheLink;
				}

				if ( $links !== [] ) {
					$infoText .= '<h3>' . wfMessage( 'smw-ask-extra-other' )->text() . '</h3>';
					$infoText .= '<ul><li>' . implode( '</li><li>', $links ) . '</li></ul>';
				}
			} else {
				$infoText .= $links;
			}

			$htmlTabs->content(
				'smw-askt-extra',
				'<div style="margin-top:15px;margin-bottom:20px;">' . $infoText . '</div>'
			);
		}

		$html .= $htmlTabs->buildHTML(
			[
				'id' => 'search',
				'class' => $this->isEditMode ? 'smw-ask-search-edit' . ( $isEmpty ? ' empty-result' : '' ) : 'smw-ask-search-compact'
			]
		);

		return $html;
	}

	private function editElements( $urlArgs ) {
		$html = '';

		$html .= Html::hidden( 'title', $this->title->getPrefixedDBKey() );
		$html .= Html::hidden( '_action', 'submit' );

		// Table for main query and printouts.
		$html .= Html::rawElement(
			'div',
			[
				'id' => 'query',
				'class' => 'smw-ask-query'
			],
			QueryInputWidget::table(
				$this->queryString,
				$urlArgs->get( 'po' )
			)
		);

		// Format selection
		$html .= Html::rawElement(
			'div',
			[
				'id' => 'format',
				'class' => "smw-ask-format"
			],
			''
		);

		// Other options fieldset
		$html .= Html::rawElement(
			'div',
			[
				'id' => 'options',
				'class' => 'smw-ask-options'
			],
			ParametersWidget::fieldset(
				$this->title,
				$this->parameters
			)
		);

		$urlArgs->set( 'eq', 'no' );

		return $html;
	}

}
