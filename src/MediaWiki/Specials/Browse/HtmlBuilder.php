<?php

namespace SMW\MediaWiki\Specials\Browse;

use Html;
use MediaWiki\MediaWikiServices;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\Store;
use SMW\Utils\HtmlDivTable;
use SMWDataValue;
use TemplateParser;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author Denny Vrandecic
 * @author mwjames
 */
class HtmlBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var boolean
	 */
	private $showoutgoing = true;

	/**
	 * To display incoming values?
	 *
	 * @var boolean
	 */
	private $showincoming = false;

	/**
	 * At which incoming property are we currently?
	 *
	 * @var integer
	 */
	private $offset = 0;

	/**
	 * How many incoming values should be asked for
	 *
	 * @var integer
	 */
	private $incomingValuesCount = 8;

	/**
	 * How many outgoing values should be asked for
	 *
	 * @var integer
	 */
	private $outgoingValuesCount = 200;

	/**
	 * How many incoming properties should be asked for
	 *
	 * @var integer
	 */
	private $incomingPropertiesCount = 21;

	/**
	 * @var array
	 */
	private $extraModules = [];

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var array
	 */
	private $language = 'en';

	private SMWDataValue $dataValue;

	private string $articletext;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param DIWikiPage $subject
	 */
	public function __construct( Store $store, DIWikiPage $subject ) {
		$this->store = $store;
		$this->subject = $subject;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {
		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function legacy() {
		$subject = [
			'dbkey' => $this->subject->getDBKey(),
			'ns' => $this->subject->getNamespace(),
			'iw' => $this->subject->getInterwiki(),
			'subobject' => $this->subject->getSubobjectName(),
		];

		return Html::rawElement(
			'div',
			[
				'data-subject' => json_encode( $subject, JSON_UNESCAPED_UNICODE ),
				'data-options' => json_encode( $this->options )
			],
			$this->buildHTML()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function placeholder() {
		$subject = [
			'dbkey' => $this->subject->getDBKey(),
			'ns' => $this->subject->getNamespace(),
			'iw' => $this->subject->getInterwiki(),
			'subobject' => $this->subject->getSubobjectName(),
		];

		$this->language = $this->getOption( 'lang' ) !== null ? $this->getOption( 'lang' ) : Message::USER_LANGUAGE;

		return Html::rawElement(
			'div',
			[
				'class' => 'smwb-container',
				'data-subject' => json_encode( $subject, JSON_UNESCAPED_UNICODE ),
				'data-options' => json_encode( $this->options )
			],
			Html::rawElement(
				'div',
				[
					'class' => 'smwb-status'
				],
				Html::rawElement(
					'noscript',
					[],
					Html::errorBox(
						Message::get( 'smw-noscript', Message::PARSE, $this->language )
					)
				)
			) . Html::rawElement(
				'div',
				[
					'class' => 'smwb-emptysheet is-disabled'
				],
				Html::rawElement(
					'span',
					[
						'class' => 'smw-overlay-spinner large inline'
					]
				) . $this->buildEmptyHTML()
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function buildHTML() {
		if ( ( $offset = $this->getOption( 'offset' ) ) ) {
			$this->offset = $offset;
		}

		$this->language = $this->getOption( 'lang' ) !== null ? $this->getOption( 'lang' ) : Message::USER_LANGUAGE;

		$this->outgoingValuesCount = $this->getOption( 'valuelistlimit.out', 200 );

		if ( $this->getOption( 'showAll' ) ) {
			$this->incomingValuesCount = $this->getOption( 'valuelistlimit.in', 21 );
			$this->incomingPropertiesCount = - 1;
			$this->showoutgoing = true;
			$this->showincoming = true;
		}

		if ( $this->getOption( 'dir' ) === 'both' || $this->getOption( 'dir' ) === 'in' ) {
			$this->showincoming = true;
		}

		if ( $this->getOption( 'dir' ) === 'in' ) {
			$this->showoutgoing = false;
		}

		if ( $this->getOption( 'dir' ) === 'out' ) {
			$this->showincoming = false;
		}

		return $this->createHTML();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function buildEmptyHTML() {
		$factboxHtml = '';
		$form = '';

		$this->dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->subject
		);

		$semanticData = new SemanticData( $this->subject );
		$this->articletext = $this->dataValue->getWikiValue();

		if ( $this->getOption( 'showAll' ) ) {
			$this->showoutgoing = true;
			$this->showincoming = true;
		}

		$factboxHtml .= $this->displayData( $semanticData, true, false, true );

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$form = FieldBuilder::createQueryForm( $this->articletext, $this->language );
		}

		$templateParser = new TemplateParser( __DIR__ . '/../../../../templates' );
		$data = [
			'data-header' => $this->getHeaderData(),
			'html-factbox' => $factboxHtml,
			'data-pagination' => $this->getPaginationData( false )
		];
		return $templateParser->processTemplate( 'Factbox', $data ) . $form;
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 */
	private function createHTML() {
		$factboxHtml = '';
		$leftside = true;
		$more = false;

		$this->dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->subject
		);

		if ( !$this->dataValue->isValid() ) {
			return '';
		}

		$semanticData = new SemanticData(
			$this->dataValue->getDataItem()
		);

		$this->dataValue->setOption( $this->dataValue::OPT_USER_LANGUAGE, $this->language );

		if ( $this->showoutgoing ) {

			$requestOptions = new RequestOptions();
			$requestOptions->setLimit( $this->outgoingValuesCount + 1 );
			$requestOptions->sort = true;

			// Restrict the request otherwise the entire SemanticData record
			// is fetched which can in case of a subject with a large
			// subobject/subpage pool create excessive DB queries
			$requestOptions->conditionConstraint = true;

			$semanticData = $this->store->getSemanticData(
				$this->dataValue->getDataItem(),
				$requestOptions
			);

			$factboxHtml .= $this->displayData( $semanticData, $leftside );
		}

		if ( $this->showincoming ) {
			list( $indata, $more ) = $this->getInData();

			if ( !$this->getOption( 'showInverse' ) ) {
				$leftside = !$leftside;
			}

			$factboxHtml .= $this->displayData( $indata, $leftside, true );
		}

		$this->articletext = $this->dataValue->getWikiValue();

		$templateParser = new TemplateParser( __DIR__ . '/../../../../templates' );
		$data = [
			'data-header' => $this->getHeaderData(),
			'html-factbox' => $factboxHtml,
			'data-pagination' => $this->getPaginationData( $more )
		];
		$html = $templateParser->processTemplate( 'Factbox', $data );

		MediaWikiServices::getInstance()
			->getHookContainer()
			->run(
				'SMW::Browse::AfterDataLookupComplete',
				[
					$this->store,
					$semanticData,
					&$html,
					&$this->extraModules
				]
			);

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$html .= FieldBuilder::createQueryForm( $this->articletext, $this->language );
		}

		$html .= Html::element(
			'div',
			[
				'class' => 'smwb-modules',
				'data-modules' => json_encode( $this->extraModules )
			]
		);

		return $html;
	}

	/**
	 * Creates the HTML table displaying the data of one subject.
	 */
	private function displayData( SemanticData $semanticData, $left = true, $incoming = false, $isLoading = false ) {
		// Some of the CSS classes are different for the left or the right side.
		// In this case, there is an "i" after the "smwb-". This is set here.
		// This is only applied to the factbox class
		$factboxGroupClass = $left ? 'smw-factbox-group' : 'smw-factbox-group smw-factbox-group--inversed';
		$noresult = true;

		$contextPage = $semanticData->getSubject();
		$diProperties = $semanticData->getProperties();

		$showGroup = $this->getOption( 'showGroup' ) && $this->getOption( 'group' ) !== 'hide';
		$applicationFactory = ApplicationFactory::getInstance();

		$groupFormatter = new GroupFormatter(
			$applicationFactory->getPropertySpecificationLookup(),
			$applicationFactory->singleton( 'SchemaFactory' )->newSchemaFinder( $this->store )
		);

		$groupFormatter->showGroup( $showGroup );
		$groupFormatter->findGroupMembership( $diProperties );

		$html = HtmlDivTable::open(
			[
				'class' => $factboxGroupClass
			]
		);

		foreach ( $diProperties as $group => $properties ) {

			if ( $group !== '' ) {

				$c = HtmlDivTable::cell(
					$groupFormatter->getGroupLink( $group ) . '<span></span>',
					[
						"class" => 'smwb-cell smwb-propval'
					]
				);

				$html .= HtmlDivTable::close();
				$html .= HtmlDivTable::open(
					[
						'class' => "{$factboxGroupClass} smwb-group"
					]
				);

				$html .= HtmlDivTable::row(
					$c,
					[
						"class" => "smwb-propvalue"
					]
				);

				$html .= HtmlDivTable::close();

				$html .= HtmlDivTable::open(
					[
						'class' => $factboxGroupClass
					]
				);
			}

			$html .= $this->buildHtmlFromData(
				$semanticData,
				$properties,
				$group,
				$incoming,
				$left,
				$noresult
			);
		}

		if ( !$isLoading && !$incoming && $showGroup ) {
			$html .= $this->getGroupMessageClassLinks(
				$groupFormatter,
				$semanticData
			);
		}

		if ( $noresult ) {
			$noMsgKey = $incoming ? 'smw_browse_no_incoming' : 'smw_browse_no_outgoing';

			$rColumn = HtmlDivTable::cell(
				'',
				[
					"class" => 'smwb-cell smwb-prophead'
				]
			);

			$lColumn = HtmlDivTable::cell(
				Message::get( $isLoading ? 'smw-browse-from-backend' : $noMsgKey, Message::ESCAPED, $this->language ),
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			$html .= HtmlDivTable::row(
				( $left ? ( $rColumn . $lColumn ) : ( $lColumn . $rColumn ) ),
				[
					"class" => "smwb-propvalue"
				]
			);
		}

		$html .= HtmlDivTable::close();

		return $html;
	}

	/**
	 * Builds HTML content that matches a group of properties and creates the
	 * display of assigned values.
	 */
	private function buildHtmlFromData( $semanticData, $properties, $group, $incoming, $left, &$noresult ) {
		$html = '';
		$group = mb_strtolower( str_replace( ' ', '-', $group ) );

		$contextPage = $semanticData->getSubject();
		$showInverse = $this->getOption( 'showInverse' );
		$showSort = $this->getOption( 'showSort' );

		$comma = Message::get(
			'comma-separator',
			Message::ESCAPED,
			$this->language
		);

		$and = Message::get(
			'and',
			Message::ESCAPED,
			$this->language
		);

		$dataValueFactory = DataValueFactory::getInstance();

		// Sort by label instead of the key which may start with `_` or `__`
		// and thereby distorts the lexicographical order
		usort( $properties, function ( $a, $b ) {
			return strnatcmp( $a->getLabel(), $b->getLabel() );
		} );

		foreach ( $properties as $diProperty ) {

			$dvProperty = $dataValueFactory->newDataValueByItem(
				$diProperty,
				null
			);

			$dvProperty->setOption( $this->dataValue::OPT_USER_LANGUAGE, $this->language );

			$dvProperty->setContextPage(
				$contextPage
			);

			$propertyLabel = ValueFormatter::getPropertyLabel(
				$dvProperty,
				$incoming,
				$showInverse
			);

			// Make the sortkey visible which is otherwise hidden from the user
			if ( $showSort && $diProperty->getKey() === '_SKEY' ) {
				$propertyLabel = Message::get( 'smw-property-predefined-label-skey', Message::TEXT, $this->language );
			}

			if ( $propertyLabel === null ) {
				continue;
			}

			$head = HtmlDivTable::cell(
				$propertyLabel,
				[
					"class" => 'smwb-cell smwb-prophead' . ( $group !== '' ? " smwb-group-$group" : '' )
				]
			);

			$values = $semanticData->getPropertyValues( $diProperty );

			if ( $incoming && ( count( $values ) >= $this->incomingValuesCount ) ) {
				$moreIncoming = true;
				$moreOutgoing = false;
				array_pop( $values );
			} elseif ( !$incoming && ( count( $values ) >= $this->outgoingValuesCount ) ) {
				$moreIncoming = false;
				$moreOutgoing = true;
				array_pop( $values );
			} else {
				$moreIncoming = false;
				$moreOutgoing = false;
			}

			$list = [];
			$value_html = '';

			foreach ( $values as $dataItem ) {
				if ( $incoming ) {
					$dv = $dataValueFactory->newDataValueByItem( $dataItem, null );
				} else {
					$dv = $dataValueFactory->newDataValueByItem( $dataItem, $diProperty );
				}

				$list[] = Html::rawElement(
					'span',
					[
						'class' => 'smwb-value'
					],
					ValueFormatter::getFormattedValue( $dv, $dvProperty, $incoming )
				);
			}

			$last = array_pop( $list );
			$value_html = implode( $comma, $list );

			if ( ( $moreOutgoing || $moreIncoming ) && $last !== '' ) {
				$value_html .= $comma . $last;
			} elseif ( $list !== [] && $last !== '' ) {
				$value_html .= '&nbsp;' . $and . '&nbsp;' . $last;
			} else {
				$value_html .= $last;
			}

			$hook = false;

			if ( $moreIncoming ) {
				// Added in 2.3
				// link to the remaining incoming pages
				MediaWikiServices::getInstance()
					->getHookContainer()
					->run(
						'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate',
						[
							$diProperty,
							$contextPage,
							&$value_html,
							$this->store
						]
					);
			}

			if ( $hook ) {
				$value_html .= Html::element(
					'a',
					[
						'href' => \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( [
							 'property' => $dvProperty->getWikiValue(),
							 'value' => $this->dataValue->getWikiValue()
						] )
					],
					wfMessage( 'smw_browse_more' )->text()
				);
			}

			if ( $moreOutgoing ) {
				$value_html .= Html::element(
					'a',
					[
						'href' => \SpecialPage::getSafeTitleFor( 'PageProperty' )->getLocalURL( [
							 'type' => $dvProperty->getWikiValue(),
							 'from' => $this->dataValue->getWikiValue()
						] )
					],
					wfMessage( 'smw_browse_more' )->text()
				);
			}

			$body = HtmlDivTable::cell(
				$value_html,
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			// display row
			$html .= HtmlDivTable::row(
				( $left ? ( $head . $body ) : ( $body . $head ) ),
				[
					"class" => 'smwb-propvalue'
				]
			);

			$noresult = false;
		}

		return $html;
	}

	/**
	 * Creates a Semantic Data object with the incoming properties instead of the
	 * usual outgoing properties.
	 */
	private function getInData() {
		$indata = new SemanticData(
			$this->dataValue->getDataItem()
		);

		$propRequestOptions = new RequestOptions();
		$propRequestOptions->sort = true;
		$propRequestOptions->setLimit( $this->incomingPropertiesCount );

		if ( $this->offset > 0 ) {
			$propRequestOptions->offset = $this->offset;
		}

		$incomingProperties = $this->store->getInProperties(
			$this->dataValue->getDataItem(),
			$propRequestOptions
		);

		$more = false;

		if ( count( $incomingProperties ) == $this->incomingPropertiesCount ) {
			$more = true;

			// drop the last one
			array_pop( $incomingProperties );
		}

		$valRequestOptions = new RequestOptions();
		$valRequestOptions->sort = true;
		$valRequestOptions->setLimit( $this->incomingValuesCount );

		foreach ( $incomingProperties as $property ) {

			$values = $this->store->getPropertySubjects(
				$property,
				$this->dataValue->getDataItem(),
				$valRequestOptions
			);

			foreach ( $values as $dataItem ) {
				$indata->addPropertyObjectValue( $property, $dataItem );
			}
		}

		// Added in 2.3
		// Whether to show a more link or not can be set via
		// SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate
		MediaWikiServices::getInstance()
			->getHookContainer()
			->run(
				'SMW::Browse::AfterIncomingPropertiesLookupComplete',
				[
					$this->store,
					$indata,
					$valRequestOptions
				]
			);

		return [ $indata, $more ];
	}

	/**
	 * Returns HTML fragments for message classes in connection with categories
	 * linked to a property group.
	 */
	private function getGroupMessageClassLinks( $groupFormatter, $semanticData ) {
		$contextPage = $semanticData->getSubject();

		if ( $contextPage->getNamespace() !== NS_CATEGORY || !$semanticData->hasProperty( new DIProperty( '_PPGR' ) ) ) {
			return '';
		}

		$group = '';
		$html = '';

		$list = [
			'label' => $groupFormatter->getMessageClassLink(
				GroupFormatter::MESSAGE_GROUP_LABEL,
				$contextPage
			),
			'description' => $groupFormatter->getMessageClassLink(
				GroupFormatter::MESSAGE_GROUP_DESCRIPTION,
				$contextPage
			)
		];

		foreach ( $list as $k => $val ) {

			if ( $val === '' ) {
				continue;
			}

			$h = HtmlDivTable::cell(
				wfMessage( 'smw-browse-property-group-' . $k )->text(),
				[
					"class" => 'smwb-cell smwb-prophead'
				]
			) . HtmlDivTable::cell(
				$val,
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			$group .= HtmlDivTable::row(
				$h,
				[
					"class" => 'smwb-propvalue'
				]
			);
		}

		if ( $group !== '' ) {
			$h = HtmlDivTable::cell(
				wfMessage( 'smw-browse-property-group-title' )->text(),
				[
					"class" => 'smwb-cell smwb-propval'
				]
			) . HtmlDivTable::cell(
				'',
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			$html = HtmlDivTable::row(
				$h,
				[
					"class" => 'smwb-propvalue smwb-group-links'
				]
			) . $group;
		}

		return $html;
	}

	/**
	 * Build the factbox header data for Mustache to build the HTML
	 * This includes the title and the actions
	 */
	private function getHeaderData(): array {
		$actionsHtml = '';
		$group = $this->getOption( 'group' );
		$article = $this->dataValue->getLongWikiText();

		if ( $this->getOption( 'showGroup' ) ) {
			if ( $group === 'hide' ) {
				$parameters = [
					'offset'  => 0,
					'dir'     => $this->showincoming ? 'both' : 'out',
					'article' => $article,
					'group'   => 'show'
				];

				$linkMsg = 'smw-browse-show-group';
			} else {
				$parameters = [
					'offset'  => $this->offset,
					'dir'     => $this->showincoming ? 'both' : 'out',
					'article' => $article,
					'group'   => 'hide'
				];
				$linkMsg = 'smw-browse-hide-group';
			}
			$actionsHtml .= FieldBuilder::createLink( $linkMsg, $parameters, $this->language );
		}

		if ( $this->showoutgoing ) {
			if ( $this->showincoming ) {
				$parameters = [
					'offset'  => 0,
					'dir'     => 'out',
					'article' => $article,
					'group'   => $group
				];

				$linkMsg = 'smw_browse_hide_incoming';
			} else {
				$parameters = [
					'offset'  => $this->offset,
					'dir'     => 'both',
					'article' => $article,
					'group'   => $group
				];
				$linkMsg = 'smw_browse_show_incoming';
			}
			$actionsHtml .= FieldBuilder::createLink( $linkMsg, $parameters, $this->language );
		}

		return [
			'html-title' => ValueFormatter::getFormattedSubject( $this->dataValue ),
			'html-actions' => $actionsHtml
		];
	}

	/**
	 * Build the factbox pagination data for Mustache to build the HTML
	 * This includes the links with further navigation options
	 *
	 * @todo Pass required data into Mustache to build the button instead
	 * building them in FieldBuilder::createLink
	 * @todo Switch pagination button to icon buttons
	 */
	private function getPaginationData( bool $more ): array {
		if (
			$more === false ||
			$this->offset <= 0 ||
			$this->getOption( 'showAll' )
		) {
			return [];
		}

		$article = $this->dataValue->getLongWikiText();

		$offset = max( $this->offset - $this->incomingPropertiesCount + 1, 0 );
		$parameters = [
			'offset'  => $offset,
			'dir'     => $this->showoutgoing ? 'both' : 'in',
			'article' => $article
		];
		$linkMsg = 'smw_result_prev';
		$prevHtml = ( $this->offset == 0 ) ? wfMessage( $linkMsg )->escaped() : FieldBuilder::createLink( $linkMsg, $parameters, $this->language );

		$offset = $this->offset + $this->incomingPropertiesCount - 1;
		$parameters = [
			'offset'  => $offset,
			'dir'     => $this->showoutgoing ? 'both' : 'in',
			'article' => $article
		];
		$linkMsg = 'smw_result_next';
		$nextHtml = $more ? FieldBuilder::createLink( $linkMsg, $parameters, $this->language ) : wfMessage( $linkMsg )->escaped();

		$status = sprintf( '%s %d – %d',
			wfMessage( 'smw_result_results' )->escaped(),
			$this->offset + 1,
			$offset
		);

		return [
			'html-pagination-prev' => $prevHtml,
			'footer-content' => $status,
			'html-pagination-next' => $nextHtml
		];
	}

}
