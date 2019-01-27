<?php

namespace SMW\MediaWiki\Search\Form;

use Html;
use MWNamespace;
use SMW\Message;
use SpecialSearch;
use Xml;

/**
 * @note Copied from SearchFormWidget::powerSearchBox, #3126 contains the reason
 * why we need to copy the code!
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceForm {

	/**
	 * @var []
	 */
	private $activeNamespaces = [];

	/**
	 * @var []
	 */
	private $hiddenNamespaces = [];

	/**
	 * @var []
	 */
	private $searchableNamespaces = [];

	/**
	 * @var null|string
	 */
	private $token;

	/**
	 * @var null|string
	 */
	private $hideList = false;

	/**
	 * @since 3.0
	 *
	 * @param array $activeNamespaces
	 */
	public function setActiveNamespaces( array $activeNamespaces ) {
		$this->activeNamespaces = $activeNamespaces;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $hideList
	 */
	public function setHideList( $hideList ) {
		$this->hideList = (bool)$hideList;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $hiddenNamespaces
	 */
	public function setHiddenNamespaces( array $hiddenNamespaces ) {
		$this->hiddenNamespaces = $hiddenNamespaces;
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
	 * @see SearchFormWidget
	 *
	 * @since 3.0
	 *
	 * @param SpecialSearch $specialSearch
	 */
	public function checkNamespaceEditToken( SpecialSearch $specialSearch ) {

		$user = $specialSearch->getUser();

		if ( !$user->isLoggedIn() ) {
			return;
		}

		$this->token = $user->getEditToken( 'searchnamespace', $specialSearch->getRequest() );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function makeFields() {
		global $wgContLang;

		$divider = "<div class='divider'></div>";
		$rows = [];
		$tableRows = [];

		$hiddenNamespaces = array_flip( $this->hiddenNamespaces );

		foreach ( $this->searchableNamespaces as $namespace => $name ) {
			$subject = MWNamespace::getSubject( $namespace );

			if ( MWNamespace::isTalk( $namespace ) ) {
			//	continue;
			}

			if ( isset( $hiddenNamespaces[$namespace] ) ) {
				continue;
			}

			if ( !isset( $rows[$subject] ) ) {
				$rows[$subject] = "";
			}

			$name = $wgContLang->getConverter()->convertNamespace( $namespace );

			if ( $name === '' ) {
				$name = Message::get( 'blanknamespace', Message::TEXT, Message::USER_LANGUAGE );
			}

			$isChecked = in_array( $namespace, $this->activeNamespaces );

			$rows[$subject] .= Html::rawElement(
				'td',
				[],
				Xml::checkLabel( $name, "ns{$namespace}", "mw-search-ns{$namespace}", $isChecked )
			);
		}

		// Lays out namespaces in multiple floating two-column tables so they'll
		// be arranged nicely while still accomodating diferent screen widths
		foreach ( $rows as $row ) {
			$tableRows[] = "<tr>{$row}</tr>";
		}

		$namespaceTables = [];
		$display = $this->hideList ? 'none' : 'block';

		foreach ( array_chunk( $tableRows, 4 ) as $chunk ) {
			$namespaceTables[] = implode( '', $chunk );
		}

		$showSections = [
			'namespaceTables' => "<table>" . implode( '</table><table>', $namespaceTables ) . '</table>',
		];

		// Stuff to feed SpecialSearch::saveNamespaces()
		$remember = '';

		if ( $this->token ) {
			$remember = $divider . Xml::checkLabel(
				Message::get( 'powersearch-remember', Message::TEXT, Message::USER_LANGUAGE ),
				'nsRemember',
				'mw-search-powersearch-remember',
				false,
				// The token goes here rather than in a hidden field so it
				// is only sent when necessary (not every form submission)
				[ 'value' => $this->token ]
			);
		}

		if ( !$this->hideList ) {
			$val = $this->msg( 'smw-search-hide', Message::ESCAPED );
		} else {
			$val = $this->msg( 'smw-search-show', Message::ESCAPED );
		}

		return "<fieldset id='mw-searchoptions'>" .
			"<legend>" . Message::get( 'powersearch-legend', Message::ESCAPED, Message::USER_LANGUAGE ) . '</legend>' .
			"<h4>" . Message::get( 'powersearch-ns', Message::PARSE, Message::USER_LANGUAGE ) . '</h4>' .
			// populated by js if available
			"<div id='smw-search-togglensview'>" .
			'<input type="button" id="smw-togglensview" value="' . $val . '">' .
			'</div>' .
			// Use `smw-search-togglebox` instead of `mw-search-togglebox` to avoid
			// issues with the search JS before the changes of
			// (MW 1.33) https://github.com/wikimedia/mediawiki/commit/c5a61564618e156d7aa9fc876d67cf4b736b2aea
			"<div id='smw-search-togglebox' style='display:$display'>" .
			'<label>' . $this->msg( 'powersearch-togglelabel', Message::ESCAPED ) . '</label>' .
			'<input type="button" id="mw-search-toggleall" value="' .
			$this->msg( 'powersearch-toggleall', Message::ESCAPED ) . '"/>' .
			'<input type="button" id="mw-search-togglenone" value="' .
			$this->msg( 'powersearch-togglenone', Message::ESCAPED ) . '"/>' .
			'</div>' .
			"<div id='mw-search-ns' style='display:$display'>" . $divider .
			implode(
				$divider,
				$showSections
			) .
			$remember . "</div>" .
		"</fieldset>";
	}

	private function msg( $key, $type = Message::PARSE, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $key, $type, $lang );
	}

}
