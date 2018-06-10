<?php

namespace SMW\MediaWiki\Specials\PageProperty;

use Html;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\Options;
use SMWInfolink as Infolink;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PageBuilder {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var Linker
	 */
	private $linker;

	/**
	 * @since 3.0
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param Options $options
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, Options $options ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->options = $options;
		$this->linker = smwfGetLinker();
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $count
	 *
	 * @return string
	 */
	public function buildForm( $count = 0 ) {

		$html = Html::rawElement(
			'p',
			[
				'class' => 'plainlinks'
			],
			Message::get( 'smw-special-pageproperty-description', Message::PARSE, Message::USER_LANGUAGE )
		);

		$html .= $this->createForm( $count );

		$html .= Html::element(
			'h2',
			[],
			Message::get( 'smw-sp-searchbyproperty-resultlist-header', Message::PARSE, Message::USER_LANGUAGE )
		);

		return $html;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage[]|[] $results
	 *
	 * @return string
	 */
	public function buildHtml( array $results ) {

		if ( count( $results ) == 0 ) {
			return Message::get( 'smw_result_noresults', Message::TEXT, Message::USER_LANGUAGE );
		}

		$limit = $this->options->get( 'limit' );
		$dataValueFactory = DataValueFactory::getInstance();

		$propertyValue = $dataValueFactory->newPropertyValueByLabel(
			$this->options->get( 'property' )
		);

		$property = $propertyValue->getDataItem();

		$isBrowsableType = DataTypeRegistry::getInstance()->isBrowsableType(
			$property->findPropertyTypeID()
		);

		$list = [];
		$count = $limit + 1;

		foreach ( $results as $dataItem ) {
			$count--;
			$link = '';

			if ( $count < 1 ) {
				continue;
			}

			$dataValue = $dataValueFactory->newDataValueByItem(
				$dataItem,
				$property
			);

			$link = $dataValue->getLongHTMLText( $this->linker );

			if ( $isBrowsableType && $this->options->safeGet( 'from', '' ) !== '' ) {
				$val = $dataValue->getLongWikiText();
				$infolink = Infolink::newBrowsingLink( '+', $val );
				$infolink->setLinkAttributes( [ 'title' => $val ] );
			} else {
				$val = $dataValue->getWikiValue();
				$infolink = Infolink::newPropertySearchLink( '+', $property->getLabel(), $val );
				$infolink->setLinkAttributes( [ 'title' => $val ] );
			}

			$link .= '&#160;&#160;' . $infolink->getHTML( $this->linker );
			$list[] = $link;
		}

		return Html::rawElement(
			'ul',
			[],
			'<li>' . implode('</li><li>', $list ) . '</li>'
		);
	}

	private function createForm( $count ) {

		// Precaution to avoid any inline breakage caused by a div element
		// within a paragraph (e.g Highlighter content)
		// $resultMessage = str_replace( 'div', 'span', $resultMessage );

		$this->htmlFormRenderer
			->setName( 'pageproperty' )
			->withFieldset()
			->addParagraph( Message::get( 'smw_pp_docu', Message::TEXT, Message::USER_LANGUAGE ) )
			->addPaging(
				$this->options->safeGet( 'limit', 20 ),
				$this->options->safeGet( 'offset', 0 ),
				$count )
			->addHorizontalRule()
			->openElement( 'div', [ 'class' => 'smw-special-pageproperty-input' ] )
			->addInputField(
				Message::get( 'smw_pp_from', Message::TEXT, Message::USER_LANGUAGE ),
				'from',
				$this->options->safeGet( 'from', '' ),
				'smw-article-input',
				30,
				[ 'class' => 'is-disabled' ] )
			->addNonBreakingSpace()
			->addInputField(
				Message::get( 'smw_sbv_property', Message::TEXT, Message::USER_LANGUAGE ),
				'type',
				$this->options->safeGet( 'type', '' ),
				'smw-property-input',
				20,
				[ 'class' => 'is-disabled' ] )
			->addNonBreakingSpace()
			->addSubmitButton( Message::get( 'smw_sbv_submit', Message::TEXT, Message::USER_LANGUAGE ) )
			->closeElement( 'div' );

		return $this->htmlFormRenderer->renderForm();
	}

}
