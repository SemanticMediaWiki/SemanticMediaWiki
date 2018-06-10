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
class ContentsBuilder {

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
	 * How many incoming properties should be asked for
	 * @var integer
	 */
	private $incomingPropertiesCount = 21;

	/**
	 * @var array
	 */
	private $extraModules = array();

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param DIWikiPage $subject
	 */
	public function __construct( Store $store, DIWikiPage $subject ) {
		$this->store = $store;
		$this->subject = DataValueFactory::getInstance()->newDataValueByItem( $subject );
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
	public function getOption( $key ) {

		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return null;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $json
	 */
	public function importOptionsFromJson( $json ) {
		$this->options = json_decode( $json, true );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getHtml() {

		if ( ( $offset = $this->getOption( 'offset' ) ) ) {
			$this->offset = $offset;
		}

		if ( $this->getOption( 'showAll' ) ) {
			$this->incomingValuesCount = 21;
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

		return $this->createHtml();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getEmptyHtml() {

		$html = '';
		$form = '';

		$semanticData = new SemanticData( $this->subject->getDataItem() );
		$this->articletext = $this->subject->getWikiValue();

		$html .= $this->displayHead();
		$html .= $this->displayData( $semanticData, true, false, true );
		$html .= $this->displayBottom( false );

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$form = FormHelper::getQueryForm( $this->articletext );
		}

		return Html::rawElement(
			'div',
			array(
				'class' => 'smwb-content'
			), $html
		) . $form;
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 */
	private function createHtml() {

		$html = "<div class=\"smwb-datasheet smwb-theme-light\">";

		$leftside = true;
		$modules = array();

		if ( !$this->subject->isValid() ) {
			return $html;
		}

		$semanticData = new SemanticData(
			$this->subject->getDataItem()
		);

		$html .= $this->displayHead();
		$html .= $this->displayActions();

		if ( $this->showoutgoing ) {
			$semanticData = $this->store->getSemanticData(
				$this->subject->getDataItem()
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

		$this->articletext = $this->subject->getWikiValue();
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
			$html .= FormHelper::getQueryForm( $this->articletext ) ;
		}

		$html .= Html::element(
			'div',
			array(
				'class' => 'smwb-modules',
				'data-modules' => json_encode( $this->extraModules )
			)
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

		$groupFormatter = new GroupFormatter(
			ApplicationFactory::getInstance()->getPropertySpecificationLookup()
		);

		$groupFormatter->showGroup( $showGroup);
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
			$noMsgKey = $incoming ? 'smw_browse_no_incoming':'smw_browse_no_outgoing';

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
				array_pop( $values );
			} else {
				$moreIncoming = false;
			}

			$list = [];
			$propertyValue = '';

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
			$propertyValue = implode( $comma, $list );

			if ( $moreIncoming && $last !== '' ) {
				$propertyValue .= $comma . $last;
			} elseif( $list !== [] && $last !== '' ) {
				$propertyValue .= '&nbsp;' . $and . '&nbsp;' . $last;
			} else {
				$propertyValue .= $last;
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
						&$propertyValue
					]
				);
			}

			if ( $hook ) {
				$propertyValue .= Html::element(
					'a',
					array(
						'href' => \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
							 'property' => $dvProperty->getWikiValue(),
							 'value' => $this->subject->getWikiValue()
						) )
					),
					wfMessage( 'smw_browse_more' )->text()
				);
			}

			$body = HtmlDivTable::cell(
				$propertyValue,
				[
					"class" => 'smwb-cell smwb-propval'
				]
			);

			// display row
			$html .= HtmlDivTable::row(
				( $left ? ( $head . $body ) : ( $body . $head ) ),
				array(
					"class" => "{$dirPrefix}propvalue"
				)
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
				ValueFormatter::getFormattedSubject( $this->subject ),
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
		$article = $this->subject->getLongWikiText();

		if ( $this->getOption( 'showGroup' ) ) {

			if ( $group === 'hide' ) {
				$parameters = array(
					'offset'  => 0,
					'dir'     => $this->showincoming ? 'both' : 'out',
					'article' => $article,
					'group'   => 'show'
				);

				$linkMsg = 'smw-browse-show-group';
			} else {
				$parameters = array(
					'offset'  => $this->offset,
					'dir'     => $this->showincoming ? 'both' : 'out',
					'article' => $article,
					'group'   => 'hide'
				);

				$linkMsg = 'smw-browse-hide-group';
			}

			$html .= FormHelper::createLinkFromMessage( $linkMsg, $parameters );
			$html .= '&nbsp;|&nbsp;';
		}

		if ( $this->showoutgoing ) {

			if ( $this->showincoming ) {
				$parameters = array(
					'offset'  => 0,
					'dir'     => 'out',
					'article' => $article,
					'group'   => $group
				);

				$linkMsg = 'smw_browse_hide_incoming';
			} else {
				$parameters = array(
					'offset'  => $this->offset,
					'dir'     => 'both',
					'article' => $article,
					'group'   => $group
				);

				$linkMsg = 'smw_browse_show_incoming';
			}

			$html .= FormHelper::createLinkFromMessage( $linkMsg, $parameters );
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

		$article = $this->subject->getLongWikiText();

		$open = HtmlDivTable::open(
			array(
				'class' => 'smwb-factbox'
			)
		);

		$html = HtmlDivTable::row(
			'&#160;',
			array(
				'class' => 'smwb-center'
			)
		);

		$close = HtmlDivTable::close();

		if ( $this->getOption( 'showAll' ) ) {
			return $open . $html . $close;
		}

		if ( ( $this->offset > 0 ) || $more ) {
			$offset = max( $this->offset - $this->incomingPropertiesCount + 1, 0 );

			$parameters = array(
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			);

			$linkMsg = 'smw_result_prev';

			$html .= ( $this->offset == 0 ) ? wfMessage( $linkMsg )->escaped() : FormHelper::createLinkFromMessage( $linkMsg, $parameters );

			$offset = $this->offset + $this->incomingPropertiesCount - 1;

			$parameters = array(
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			);

			$linkMsg = 'smw_result_next';

			$html .= " &#160;&#160;&#160;  <strong>" . wfMessage( 'smw_result_results' )->escaped() . " " . ( $this->offset + 1 ) .
					 " – " . ( $offset ) . "</strong>  &#160;&#160;&#160; ";
			$html .= $more ? FormHelper::createLinkFromMessage( $linkMsg, $parameters ) : wfMessage( $linkMsg )->escaped();

			$html = HtmlDivTable::row(
				$html,
				array(
					'class' => 'smwb-center'
				)
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
			$this->subject->getDataItem()
		);

		$propRequestOptions = new RequestOptions();
		$propRequestOptions->sort = true;
		$propRequestOptions->setLimit( $this->incomingPropertiesCount );

		if ( $this->offset > 0 ) {
			$propRequestOptions->offset = $this->offset;
		}

		$incomingProperties = $this->store->getInProperties(
			$this->subject->getDataItem(),
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
				$this->subject->getDataItem(),
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
				array(
					"class" => "{$dirPrefix}propvalue"
				)
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
				array(
					"class" => "{$dirPrefix}propvalue smwb-group-links"
				)
			) . $group;
		}

		return $html;
	}

}
