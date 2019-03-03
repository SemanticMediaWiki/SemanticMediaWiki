<?php

namespace SMW\MediaWiki\Specials\Browse;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\Store;
use SMW\Utils\HtmlDivTable;

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
	 * @var integer
	 */
	private $offset = 0;

	/**
	 * How many incoming values should be asked for
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
		return Html::rawElement(
			'div',
			[
				'data-subject' => $this->subject->getHash(),
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
		return Html::rawElement(
			'div',
			[
				'class' => 'smwb-container',
				'data-subject' => $this->subject->getHash(),
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
					Html::rawElement(
						'div',
						[
							'class' => 'smw-callout smw-callout-error',
						],
						Message::get( 'smw-noscript', Message::PARSE )
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

		$html = '';
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

		$html .= $this->displayHead();
		$html .= $this->displayActions();
		$html .= $this->displayData( $semanticData, true, false, true );
		$html .= $this->displayBottom( false );

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$form = FieldBuilder::createQueryForm( $this->articletext );
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smwb-content'
			], $html
		) . $form;
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 */
	private function createHTML() {

		$html = "<div class=\"smwb-datasheet smwb-theme-light\">";

		$leftside = true;
		$modules = [];

		$this->dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->subject
		);

		if ( !$this->dataValue->isValid() ) {
			return $html;
		}

		$semanticData = new SemanticData(
			$this->dataValue->getDataItem()
		);

		$html .= $this->displayHead();
		$html .= $this->displayActions();

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

			$html .= $this->displayData( $semanticData, $leftside );
		}

		$html .= $this->displayCenter();

		if ( $this->showincoming ) {
			list( $indata, $more ) = $this->getInData();

			if ( !$this->getOption( 'showInverse' ) ) {
				$leftside = !$leftside;
			}

			$html .= $this->displayData( $indata, $leftside, true );
			$html .= $this->displayBottom( $more );
		}

		$this->articletext = $this->dataValue->getWikiValue();
		$html .= "</div>";

		\Hooks::run(
			'SMW::Browse::AfterDataLookupComplete',
			[
				$this->store,
				$semanticData,
				&$html,
				&$this->extraModules
			]
		);

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$html .= FieldBuilder::createQueryForm( $this->articletext ) ;
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
		$dirPrefix = $left ? 'smwb-' : 'smwb-i';
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
				'class' => "{$dirPrefix}factbox" . ( $groupFormatter->hasGroups() ? '' : ' smwb-bottom' )
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
						'class' => "{$dirPrefix}factbox smwb-group"
					]
				);

				$html .= HtmlDivTable::row(
					$c,
					[
						"class" => "{$dirPrefix}propvalue"
					]
				);

				$html .= HtmlDivTable::close();
				$class = ( $groupFormatter->isLastGroup( $group ) ? ' smwb-bottom' : '' );

				$html .= HtmlDivTable::open(
					[
						'class' => "{$dirPrefix}factbox{$class}"
					]
				);
			}

			$html .= $this->buildHtmlFromData(
				$semanticData,
				$properties,
				$group,
				$incoming,
				$left,
				$dirPrefix,
				$noresult
			);
		}

		if ( !$isLoading  && !$incoming && $showGroup ) {
			$html .= $this->getGroupMessageClassLinks(
				$groupFormatter,
				$semanticData,
				$dirPrefix
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
				wfMessage( $isLoading ? 'smw-browse-from-backend' : $noMsgKey )->escaped(),
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			$html .= HtmlDivTable::row(
				( $left ? ( $rColumn . $lColumn ):( $lColumn . $rColumn ) ),
				[
					"class" => "{$dirPrefix}propvalue"
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
	private function buildHtmlFromData( $semanticData, $properties, $group, $incoming, $left, $dirPrefix, &$noresult ) {

		$html = '';
		$group = mb_strtolower( str_replace( ' ', '-', $group ) );

		$contextPage = $semanticData->getSubject();
		$showInverse = $this->getOption( 'showInverse' );
		$showSort = $this->getOption( 'showSort' );

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

		$dataValueFactory = DataValueFactory::getInstance();

		foreach ( $properties as $diProperty ) {

			$dvProperty = $dataValueFactory->newDataValueByItem(
				$diProperty,
				null
			);

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
				$propertyLabel = Message::get( 'smw-property-predefined-label-skey', Message::TEXT, Message::USER_LANGUAGE );
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
						'class' => "{$dirPrefix}value"
					],
					ValueFormatter::getFormattedValue( $dv, $dvProperty, $incoming )
				);
			}

			$last = array_pop( $list );
			$value_html = implode( $comma, $list );

			if ( ( $moreOutgoing || $moreIncoming ) && $last !== '' ) {
				$value_html .= $comma . $last;
			} elseif( $list !== [] && $last !== '' ) {
				$value_html .= '&nbsp;' . $and . '&nbsp;' . $last;
			} else {
				$value_html .= $last;
			}

			$hook = false;

			if ( $moreIncoming ) {
				// Added in 2.3
				// link to the remaining incoming pages
				$hook = \Hooks::run(
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
					"class" => "{$dirPrefix}propvalue"
				]
			);

			$noresult = false;
		}

		return $html;
	}

	/**
	 * Displays the subject that is currently being browsed to.
	 */
	private function displayHead() {
		return HtmlDivTable::table(
			HtmlDivTable::row(
				ValueFormatter::getFormattedSubject( $this->dataValue ),
				[
					'class' => 'smwb-title'
				]
			),
			[
				'class' => 'smwb-factbox'
			]
		);
	}

	/**
	 * Creates the HTML for the center bar including the links with further
	 * navigation options.
	 */
	private function displayActions() {

		$html = '';
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

			$html .= FieldBuilder::createLink( $linkMsg, $parameters );
			$html .= '<span class="smwb-action-separator">&nbsp;</span>';
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

			$html .= FieldBuilder::createLink( $linkMsg, $parameters );
		}

		return HtmlDivTable::table(
			HtmlDivTable::row(
				$html . "&#160;\n",
				[
					'class' => 'smwb-actions'
				]
			),
			[
				'class' => 'smwb-factbox'
			]
		);
	}

	private function displayCenter() {
		return HtmlDivTable::table(
			HtmlDivTable::row(
				"&#160;\n",
				[
					'class' => 'smwb-center'
				]
			),
			[
				'class' => 'smwb-factbox'
			]
		);
	}

	/**
	 * Creates the HTML for the bottom bar including the links with further
	 * navigation options.
	 */
	private function displayBottom( $more ) {

		$article = $this->dataValue->getLongWikiText();

		$open = HtmlDivTable::open(
			[
				'class' => 'smwb-factbox'
			]
		);

		$html = HtmlDivTable::row(
			'&#160;',
			[
				'class' => 'smwb-center'
			]
		);

		$close = HtmlDivTable::close();

		if ( $this->getOption( 'showAll' ) ) {
			return $open . $html . $close;
		}

		if ( ( $this->offset > 0 ) || $more ) {
			$offset = max( $this->offset - $this->incomingPropertiesCount + 1, 0 );

			$parameters = [
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			];

			$linkMsg = 'smw_result_prev';

			$html .= ( $this->offset == 0 ) ? wfMessage( $linkMsg )->escaped() : FieldBuilder::createLink( $linkMsg, $parameters );

			$offset = $this->offset + $this->incomingPropertiesCount - 1;

			$parameters = [
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			];

			$linkMsg = 'smw_result_next';

			$html .= " &#160;&#160;&#160;  <strong>" . wfMessage( 'smw_result_results' )->escaped() . " " . ( $this->offset + 1 ) .
					 " – " . ( $offset ) . "</strong>  &#160;&#160;&#160; ";
			$html .= $more ? FieldBuilder::createLink( $linkMsg, $parameters ) : wfMessage( $linkMsg )->escaped();

			$html = HtmlDivTable::row(
				$html,
				[
					'class' => 'smwb-center'
				]
			);
		}

		return $open . $html . $close;
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
		\Hooks::run(
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
	private function getGroupMessageClassLinks( $groupFormatter, $semanticData, $dirPrefix ) {

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
					"class" => "{$dirPrefix}propvalue"
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
					"class" => "{$dirPrefix}propvalue smwb-group-links"
				]
			) . $group;
		}

		return $html;
	}

}
