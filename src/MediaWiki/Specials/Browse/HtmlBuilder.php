<?php

namespace SMW\MediaWiki\Specials\Browse;

use Html;
use MediaWiki\MediaWikiServices;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\RequestOptions;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMWDataValue;
use TemplateParser;

/**
 * @license GPL-2.0-or-later
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
	 * @var bool
	 */
	private $showoutgoing = true;

	/**
	 * To display incoming values?
	 *
	 * @var bool
	 */
	private $showincoming = false;

	/**
	 * At which incoming property are we currently?
	 *
	 * @var int
	 */
	private $offset = 0;

	/**
	 * How many incoming values should be asked for
	 *
	 * @var int
	 */
	private $incomingValuesCount = 8;

	/**
	 * How many outgoing values should be asked for
	 *
	 * @var int
	 */
	private $outgoingValuesCount = 200;

	/**
	 * How many incoming properties should be asked for
	 *
	 * @var int
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
	 * Return the Mustache data for the placeholder state
	 *
	 * @since 5.0
	 */
	public function getPlaceholderData(): array {
		$subject = [
			'dbkey' => $this->subject->getDBKey(),
			'ns' => $this->subject->getNamespace(),
			'iw' => $this->subject->getInterwiki(),
			'subobject' => $this->subject->getSubobjectName(),
		];

		$this->language = $this->getOption( 'lang' ) !== null ? $this->getOption( 'lang' ) : Message::USER_LANGUAGE;

		$this->dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->subject
		);

		$this->articletext = $this->dataValue->getWikiValue();

		if ( $this->getOption( 'showAll' ) ) {
			$this->showoutgoing = true;
			$this->showincoming = true;
		}

		$msg = Message::get( 'smw-browse-from-backend', Message::ESCAPED, $this->language );

		$data = [
			'subject' => json_encode( $subject, JSON_UNESCAPED_UNICODE ),
			'options' => json_encode( $this->options ),
			'html-noscript' => Html::errorBox(
				Message::get( 'smw-noscript', Message::PARSE, $this->language )
			),
			'data-factbox' => [
				'is-loading' => true,
				'data-header' => $this->getHeaderData(),
				'array-sections' => [
					[
						'html-section' => Html::noticeBox( $msg, 'smw-factbox-message' )
					]
				],
				'data-pagination' => $this->getPaginationData( false )
			]
		];

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$data['data-form'] = FieldBuilder::getQueryFormData( $this->articletext, $this->language );
		}

		return $data;
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
			$this->incomingPropertiesCount = -1;
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
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 */
	private function createHTML(): string {
		$leftside = true;
		$more = false;

		$this->dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->subject
		);

		if ( !$this->dataValue->isValid() ) {
			return '';
		}

		$contentData = [];

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

			$contentData = array_merge( $contentData, $this->getSectionsData( $semanticData, $leftside ) );
		}

		if ( $this->showincoming ) {
			[ $indata, $more ] = $this->getInData();

			if ( !$this->getOption( 'showInverse' ) ) {
				$leftside = !$leftside;
			}

			$contentData = array_merge( $contentData, $this->getSectionsData( $indata, $leftside, true ) );
		}

		$this->articletext = $this->dataValue->getWikiValue();

		$templateParser = new TemplateParser( __DIR__ . '/../../../../templates' );
		$data = [
			'data-factbox' => [
				'data-header' => $this->getHeaderData(),
				'array-sections' => $contentData,
				'data-pagination' => $this->getPaginationData( $more )
			]
		];
		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$data['data-form'] = FieldBuilder::getQueryFormData( $this->articletext, $this->language );
		}
		$html = $templateParser->processTemplate( 'SpecialBrowse', $data );

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

		$html .= Html::element(
			'div',
			[
				'class' => 'smw-browse-modules',
				'data-modules' => json_encode( $this->extraModules )
			]
		);

		return $html;
	}

	/**
	 * Creates a Semantic Data object with the incoming properties instead of the
	 * usual outgoing properties.
	 */
	private function getInData(): array {
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

		$directions = [
			'prev' => max( $this->offset - $this->incomingPropertiesCount + 1, 0 ),
			'next' => $this->offset + $this->incomingPropertiesCount - 1,
		];

		foreach ( [ 'prev', 'next' ] as $dir ) {
			$offset = $directions[$dir];
			$parameters = [
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article,
			];
			$linkMsg = 'smw_result_' . $dir;
			$condition = ( $dir === 'prev' ) ? $this->offset > 0 : $more;
			${$dir . 'Html'} = $condition
				? FieldBuilder::createLink( $linkMsg, $parameters, $this->language )
				: wfMessage( $linkMsg )->escaped();
		}

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

	/**
	 * Build the factbox section data for Mustache to build the HTML
	 */
	private function getSectionsData(
		SemanticData $semanticData,
		bool $left = true,
		bool $incoming = false
	): array {
		$noresult = true;

		$diProperties = $semanticData->getProperties();

		$showGroup = $this->getOption( 'showGroup' ) && $this->getOption( 'group' ) !== 'hide';
		$applicationFactory = ApplicationFactory::getInstance();

		$groupFormatter = new GroupFormatter(
			$applicationFactory->getPropertySpecificationLookup(),
			$applicationFactory->singleton( 'SchemaFactory' )->newSchemaFinder( $this->store )
		);

		$groupFormatter->showGroup( $showGroup );
		$groupFormatter->findGroupMembership( $diProperties );

		$data = [];

		foreach ( $diProperties as $group => $properties ) {
			$props = $this->getPropertiesData(
				$semanticData,
				$properties,
				$incoming,
				$noresult
			);

			if ( !$props ) {
				continue;
			}

			$sectionData = [
				'direction' => $left ? 'start' : 'end',
				'array-properties' => $props
			];

			if ( $group !== '' ) {
				$sectionData['group'] = $group;
				$sectionData['html-heading'] = $groupFormatter->getGroupLink( $group );
			}

			$data[] = $sectionData;
		}

		if ( !$incoming && $showGroup ) {
			$groupLinks = $this->getGroupMessageClassLinksData(
				$groupFormatter,
				$semanticData
			);
			if ( count( $groupLinks ) > 0 ) {
				$data[] = $groupLinks;
			}
		}

		if ( $noresult ) {
			$noMsgKey = $incoming ? 'smw_browse_no_incoming' : 'smw_browse_no_outgoing';
			$msg = Message::get( $noMsgKey, Message::ESCAPED, $this->language );
			$data[]['html-section'] = Html::noticeBox( $msg, 'smw-factbox-message' );
		}

		return $data;
	}

	/**
	 * Return the Mustache data to build the html that
	 * matches a group of properties and creates the display of assigned values.
	 */
	private function getPropertiesData(
		SemanticData $semanticData,
		array $properties,
		bool $incoming,
		bool &$noresult
	): array {
		$data = [];

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
		usort( $properties, static function ( $a, $b ) {
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
						'class' => 'smw-factbox-value'
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

			$noresult = false;

			$data[] = [
				'html-name' => $propertyLabel,
				'html-values' => $value_html
			];
		}

		return $data;
	}

	/**
	 * Returns the Mustache data to build the HTML for message classes
	 * in connection with categories linked to a property group.
	 */
	private function getGroupMessageClassLinksData( $groupFormatter, $semanticData ): array {
		$data = [];
		$contextPage = $semanticData->getSubject();

		if ( $contextPage->getNamespace() !== NS_CATEGORY || !$semanticData->hasProperty( new DIProperty( '_PPGR' ) ) ) {
			return $data;
		}

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

		$props = [];
		foreach ( $list as $k => $val ) {
			if ( $val === '' ) {
				continue;
			}

			$props[] = [
				'html-name' => wfMessage( 'smw-browse-property-group-' . $k )->text(),
				'html-values' => $val
			];
		}

		if ( count( $props ) === 0 ) {
			return $data;
		}

		$msg = wfMessage( 'smw-browse-property-group-title' )->text();
		$data = [
			'group' => $msg,
			'html-heading' => $msg,
			'array-properties' => $props
		];

		return $data;
	}

}
