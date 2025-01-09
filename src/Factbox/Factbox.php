<?php

namespace SMW\Factbox;

use Html;
use MediaWiki\MediaWikiServices;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DisplayTitleFinder;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Message;
use SMW\ParserData;
use SMW\SemanticData;
use SMW\Store;
use SMW\Utils\HtmlTabs;
use SMWInfolink;
use TemplateParser;
use Title;

/**
 * Class handling the "Factbox" content rendering
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class Factbox {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var DisplayTitleFinder
	 */
	private $displayTitleFinder;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var int
	 */
	private $featureSet = 0;

	/**
	 * @var bool
	 */
	protected $isVisible = false;

	/**
	 * @var string
	 */
	protected $content = null;

	/**
	 * @var array
	 */
	private $attachments = [];

	/**
	 * @var CheckMagicWords
	 */
	private $checkMagicWords;

	private AttachmentFormatter $attachmentFormatter;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param ParserData $parserData
	 * @param DisplayTitleFinder $displayTitleFinder
	 */
	public function __construct( Store $store, ParserData $parserData, DisplayTitleFinder $displayTitleFinder ) {
		$this->store = $store;
		$this->parserData = $parserData;
		$this->displayTitleFinder = $displayTitleFinder;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->attachmentFormatter = new AttachmentFormatter( $store );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $featureSet
	 */
	public function setFeatureSet( $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.1
	 *
	 * @param CheckMagicWords $checkMagicWords
	 */
	public function setCheckMagicWords( CheckMagicWords $checkMagicWords ): void {
		$this->checkMagicWords = $checkMagicWords;
	}

	/**
	 * Builds content suitable for rendering a Factbox and
	 * updating the ParserOutput accordingly
	 *
	 * @since 1.9
	 *
	 * @return Factbox
	 */
	public function doBuild() {
		$this->content = $this->fetchContent(
			$this->getMagicWords()
		);

		if ( $this->content !== '' || $this->attachments !== [] ) {
			$this->parserData->getOutput()->addModuleStyles( self::getModuleStyles() );
			$this->parserData->getOutput()->addModules( self::getModules() );
			$this->parserData->pushSemanticDataToParserOutput();
			$this->isVisible = true;
		}

		return $this;
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 */
	public function getTitle(): Title {
		return $this->parserData->getTitle();
	}

	/**
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $attachments
	 */
	public function setAttachments( array $attachments ): void {
		$this->attachments = $attachments;
	}

	/**
	 * @since 3.1
	 */
	public function getAttachmentHTML(): string {
		if ( $this->attachments === [] || !$this->hasFeature( SMW_FACTBOX_DISPLAY_ATTACHMENT ) ) {
			return '';
		}

		$templateParser = new TemplateParser( __DIR__ . '/../../templates' );
		$data = [
			'data-header' => $this->getHeaderData( DIWikiPage::newFromTitle( $this->getTitle() ) ),
			'array-sections' => [
				'html-section' => $this->attachmentFormatter->buildHTML(
					$this->attachments
				)
			]
		];
		return $templateParser->processTemplate( 'Factbox', $data );
	}

	/**
	 * Returns if content is visible
	 *
	 * @since 1.9
	 */
	public function isVisible(): bool {
		return $this->isVisible;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $list
	 * @param string $attachment
	 * @param string $derived
	 */
	public static function tabs( $list, $attachment = '', $derived = '' ): string {
		$htmlTabs = new HtmlTabs();
		$htmlTabs->setActiveTab(
			$list !== '' ? 'facts-list' : 'facts-attachment'
		);

		$htmlTabs->tab(
			'facts-list',
			Message::get( 'smw-factbox-facts', Message::TEXT, Message::USER_LANGUAGE ),
			[
				'title' => Message::get( 'smw-factbox-facts-help', Message::TEXT, Message::USER_LANGUAGE ),
				'hide' => $list === ''
			]
		);

		$htmlTabs->content(
			'facts-list',
			$list
		);

		$htmlTabs->tab(
			'facts-attachment',
			Message::get( 'smw-factbox-attachments', Message::TEXT, Message::USER_LANGUAGE ),
			[
				'title' => Message::get( 'smw-factbox-attachments-help', Message::TEXT, Message::USER_LANGUAGE ),
				'hide' => $attachment === ''
			]
		);

		$htmlTabs->content(
			'facts-attachment',
			$attachment
		);

		$htmlTabs->tab(
			'facts-derived',
			Message::get( 'smw-factbox-derived', Message::TEXT, Message::USER_LANGUAGE ),
			[
				'hide' => $derived === ''
			]
		);

		$htmlTabs->content( 'facts-derived', $derived );

		return $htmlTabs->buildHTML(
			[
				'class' => 'smw-factbox-container'
			]
		);
	}

	/**
	 * Returns magic words attached to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	protected function getMagicWords() {
		if ( $this->checkMagicWords === null ) {
			return SMW_FACTBOX_HIDDEN;
		}

		$magicWords = $this->checkMagicWords->getMagicWords(
			$this->parserData->getOutput()
		);

		return $magicWords;
	}

	/**
	 * Returns required resource module styles
	 *
	 * @since 5.0
	 */
	public static function getModuleStyles(): array {
		return [
			'ext.smw.factbox.styles'
		];
	}

	/**
	 * Returns required resource modules
	 *
	 * @todo figure out a way to load this module only when attachments exist
	 * @since 1.9
	 */
	public static function getModules(): array {
		return [
			'ext.smw.factbox'
		];
	}

	/**
	 * Returns content found for a given ParserOutput object and if the required
	 * custom data was not available then semantic data are retrieved from
	 * the store for a given subject.
	 *
	 * The method checks whether the given setting of $showfactbox requires
	 * displaying the given data at all.
	 *
	 * @since 1.9
	 */
	protected function fetchContent( int $showFactbox = SMW_FACTBOX_NONEMPTY ): string {
		if ( $showFactbox === SMW_FACTBOX_HIDDEN ) {
			return '';
		}

		$semanticData = $this->parserData->getSemanticData();

		if ( $semanticData === null || $semanticData->stubObject || $this->isEmpty( $semanticData ) ) {
			$semanticData = $this->store->getSemanticData( $this->parserData->getSubject() );
		}

		if ( $showFactbox === SMW_FACTBOX_SPECIAL && !$semanticData->hasVisibleSpecialProperties() ) {
			// show only if there are special properties
			return '';
		} elseif ( $showFactbox === SMW_FACTBOX_NONEMPTY && !$semanticData->hasVisibleProperties() ) {
			// show only if non-empty
			return '';
		}

		return $this->buildHTML( $semanticData );
	}

	/**
	 * Returns the HTML of the factbox
	 *
	 * @since 1.9
	 */
	protected function buildHTML( SemanticData $semanticData ): string {
		$html = '';
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		if ( !$hookContainer->run( 'SMW::Factbox::BeforeContentGeneration', [ &$html, $semanticData ] ) ) {
			return $html;
		}

		$this->displayTitleFinder->prefetchFromSemanticData( $semanticData );

		$templateParser = new TemplateParser( __DIR__ . '/../../templates' );
		$data = [
			'data-header' => $this->getHeaderData( $semanticData->getSubject() ),
			'array-sections' => [
				'array-properties' => $this->getPropertiesData( $semanticData )
			]
		];

		$html .= $templateParser->processTemplate( 'Factbox', $data );

		return $html;
	}

	private function isEmpty( SemanticData $semanticData ): bool {
		// MW's internal Parser does iterate the ParserOutput object several times
		// which can leave a '_SKEY' property while in fact the container is empty.
		$semanticData->removeProperty(
			new DIProperty( '_SKEY' )
		);

		return (bool)$semanticData->isEmpty();
	}

	private function hasFeature( ?int $feature ): bool {
		return ( (int)$this->featureSet & $feature ) != 0;
	}

	private function getHeaderData( DIWikiPage $subject ): array {
		$dataValue = $this->dataValueFactory->newDataValueByItem( $subject, null );

		$browselink = SMWInfolink::newBrowsingLink(
			$dataValue->getPreferredCaption(),
			$dataValue->getWikiValue(),
			''
		);

		return [
			'html-title' => Message::get( [ 'smw-factbox-head', $browselink->getWikiText() ], Message::TEXT, Message::USER_LANGUAGE ),
			'html-actions' => SMWInfolink::newInternalLink(
				Message::get( 'smw_viewasrdf', Message::TEXT, Message::USER_LANGUAGE ),
				Localizer::getInstance()->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $dataValue->getWikiValue(),
				'rdflink'
			)->getWikiText()
		];
	}

	private function getPropertiesData( SemanticData $semanticData ): array {
		$data = [];
		$properties = $semanticData->getProperties();
		if ( empty( $properties ) ) {
			/**
			 * @todo: Should we show an empty state if there are no data?
			 * We can do it by setting 'html-section' with the HTML of a notice box
			 */
			return $data;
		}

		$comma = Message::get(
			'comma-separator',
			Message::ESCAPED,
			Message::USER_LANGUAGE
		);

		$and = Message::get(
			'and',
			Message::ESCAPED,
			Message::USER_LANGUAGE
		);

		foreach ( $properties as $property ) {
			$key = $property->getKey();
			if ( $key === '_SOBJ' && !$this->hasFeature( SMW_FACTBOX_DISPLAY_SUBOBJECT ) ) {
				// Not showing subobjects
				continue;
			}

			if ( !$property->isShown() ) {
				continue;
			}

			$propertyDv = $this->dataValueFactory->newDataValueByItem( $property, null );
			$isUserDefined = $property->isUserDefined();
			$isVisible = $propertyDv->isVisible();
			$isInternal = !$isUserDefined && !$isVisible;

			if ( $isInternal ) {
				continue;
			}

			if ( $isUserDefined ) {
				$propertyDv->setCaption( $propertyDv->getWikiValue() );
			}

			$list = [];
			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {
				if ( $key === '_ATTCH_LINK' ) {
					$this->attachments[] = $dataItem;
					continue;
				}

				$dataValue = $this->dataValueFactory->newDataValueByItem( $dataItem, $property );
				if ( !$dataValue->isValid() ) {
					continue;
				}

				$outputFormat = $dataValue->getOutputFormat();
				$dataValue->setOutputFormat( $outputFormat ? $outputFormat : 'LOCL' );
				$dataValue->setOption( $dataValue::OPT_DISABLE_SERVICELINKS, true );

				$list[] = Html::rawElement(
					'span',
					[
						'class' => 'smw-factbox-value'
					],
					$dataValue->getLongWikiText( true ) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI )
				);
			}

			if ( $list === [] ) {
				continue;
			}

			/**
			 * @todo: PHP does not have Intl.ListFormat() like in JS.
			 * Considered that we use this a lot in SMW, it should be refactored into
			 * an shared utility method.
			 */
			$valueHtml = '';
			if ( count( $list ) === 1 ) {
				$valueHtml = $list[0];
			} else {
				$last = array_pop( $list );
				$valueHtml = implode( $comma, $list ) . '&nbsp;' . $and . '&nbsp;' . $last;
			}

			$data[] = [
				'html-name' => $propertyDv->getShortWikiText( true ),
				'html-values' => $valueHtml
			];
		}

		return $data;
	}

}
