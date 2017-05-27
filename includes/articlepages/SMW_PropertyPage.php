<?php

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Localizer;
use SMW\RequestOptions;
use SMW\StringCondition;
use SMW\PropertyRegistry;
use SMWDataValue as DataValue;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\Content\PropertyPageMessageHtmlBuilder;
use SMW\PropertySpecificationReqExaminer;

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages. Very similar to CategoryPage, but with different printout
 * that also displays values for each subject with the given property.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */
class SMWPropertyPage extends SMWOrderedListPage {

	/**
	 * @see SMWOrderedListPage::initParameters()
	 * @note We use a smaller limit here; property pages might become large.
	 */
	protected function initParameters() {
		global $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		$this->mProperty = DIProperty::newFromUserLabel( $this->mTitle->getText() );
		$this->store = ApplicationFactory::getInstance()->getStore();
		$this->propertyValue = DataValueFactory::getInstance()->newDataItemValue( $this->mProperty );
		return true;
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected function getHtml() {

		if ( !$this->store->getRedirectTarget( $this->mProperty )->equals( $this->mProperty ) ) {
			return '';
		}

		$dv = DataValueFactory::getInstance()->newDataValueByItem(
			$this->mProperty
		);

		$title = $dv->getFormattedLabel( DataValueFormatter::WIKI_LONG );
		$this->getContext()->getOutput()->setPageTitle( $title );

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = true;

		// +1 look ahead
		$requestOptions->setLimit( $GLOBALS['smwgRedirectPropertyListLimit'] + 1 );

		$list = $this->getPropertyList(
			new DIProperty( '_REDI' ),
			$requestOptions,
			$GLOBALS['smwgRedirectPropertyListLimit'],
			'smw-propertylist-redirect'
		);

		$requestOptions->setLimit( $GLOBALS['smwgSubPropertyListLimit'] + 1 );

		$list .= $this->getPropertyList(
			new DIProperty( '_SUBP' ),
			$requestOptions,
			$GLOBALS['smwgSubPropertyListLimit'],
			'smw-propertylist-subproperty'
		);

		$list .= $this->getPropertyValueList();

		$result = ( $list !== '' ? Html::element( 'div', array( 'id' => 'smwfootbr' ) ) . $list : '' );

		return $result;
	}

	/**
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function getIntroductoryText() {

		if ( !$this->store->getRedirectTarget( $this->mProperty )->equals( $this->mProperty ) ) {
			return '';
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$propertySpecificationReqExaminer = new PropertySpecificationReqExaminer(
			$this->store
		);

		$propertySpecificationReqExaminer->setSemanticData(
			$this->getSemanticData()
		);

		$propertySpecificationReqExaminer->setEditProtectionRight(
			$applicationFactory->getSettings()->get( 'smwgEditProtectionRight' )
		);

		$propertyPageMessageHtmlBuilder = new PropertyPageMessageHtmlBuilder(
			$this->store,
			$propertySpecificationReqExaminer
		);

		$propertyPageMessageHtmlBuilder->hasEditProtection(
			$applicationFactory->singleton( 'EditProtectionValidator' )->hasEditProtection( $this->mTitle )
		);

		return $propertyPageMessageHtmlBuilder->createMessageBody( $this->mProperty );
	}

	protected function getTopIndicators() {

		$propertyValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->mProperty
		);

		// Label that corresponds to the display and sort characteristics
		$searchLabel = $this->mProperty->isUserDefined() ? $propertyValue->getSearchLabel() : $this->mProperty->getCanonicalLabel();
		$usageCountHtml = '';

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->addStringCondition( $searchLabel, StringCondition::COND_EQ );

		$cachedLookupList = $this->store->getPropertiesSpecial( $requestOptions );
		$usageList = $cachedLookupList->fetchList();

		if ( $usageList && $usageList !== array() ) {
			$usage = end( $usageList );
			$usageCount = $usage[1];
			$usageCountHtml = Html::rawElement(
				'div', array(
					'title' => wfMessage( 'smw-property-indicator-last-count-update', $this->getContext()->getLanguage()->timeanddate( $cachedLookupList->getTimestamp() ) )->text(),
					'class' => 'smw-property-page-indicator usage-count' . ( $usageCount < 25000 ? ( $usageCount > 5000 ? ' moderate' : '' ) : ' high' ) ),
				$usageCount
			);
		}

		$type = Html::rawElement(
				'div',
				array(
					'class' => 'smw-property-page-indicator property-type',
					'title' => wfMessage( 'smw-property-indicator-type-info', $this->mProperty->isUserDefined() )->parse()
			), ( $this->mProperty->isUserDefined() ? 'U' : 'S' )
		);

		return array(
			'smw-prop-count' => $usageCountHtml,
			'smw-prop-type' => $type
		);
	}

	/**
	 * Get the HTML for displaying subproperties of this property. This list
	 * is usually short and we implement no additional navigation.
	 *
	 * @return string
	 */
	protected function getPropertyList( $property, $requestOptions, $listLimit, $header ) {

		$propertyList =  $this->store->getPropertySubjects(
			$property,
			$this->getDataItem(),
			$requestOptions
		);

		$more = false;

		// Pop the +1 look ahead from the list
		if ( count( $propertyList ) > $listLimit ) {
			array_pop( $propertyList );
			$more = true;
		}

		$result = '';
		$resultCount = count( $propertyList );

		if ( $more ) {
			$message = Html::rawElement(
				'span',
				array( 'class' => 'plainlinks' ),
				wfMessage( 'smw-propertylist-count-with-restricted-note', $resultCount, $listLimit )->parse()
			);
		} else {
			$message = wfMessage( 'smw-propertylist-count', $resultCount )->text();
		}

		if ( $resultCount > 0 ) {
			$titleText = htmlspecialchars( $this->mTitle->getText() );
			$result .= "<div id=\"{$header}\">" . Html::rawElement( 'h2', array(), wfMessage( $header . '-header', $titleText )->text() ) . "\n<p>";

			if ( !$this->mProperty->isUserDefined() ) {
				$result .= wfMessage( 'smw_isspecprop' )->text() . ' ';
			}

			$result .= $message . "</p>";

			if ( $resultCount < 6 ) {
				$result .= SMWPageLister::getShortList( 0, $resultCount, $propertyList, $property );
			} else {
				$result .= SMWPageLister::getColumnList( 0, $resultCount, $propertyList, $property );
			}

			$result .= "\n</div>";
		}

		return $result;
	}

	/**
	 * Get the HTML for displaying values of this property, based on the
	 * current from/until and limit settings.
	 *
	 * @return string
	 */
	protected function getPropertyValueList() {
		global $smwgPropertyPagingLimit;

		 // limit==0: configuration setting to disable this completely
		if ( $this->limit < 1 ) {
			return '';
		}

		$request = $this->getContext()->getRequest();

		$diWikiPages = array();
		$options = SMWPageLister::getRequestOptions( $this->limit, $this->from, $this->until );

		$options->limit = intval( $request->getVal( 'limit', $smwgPropertyPagingLimit ) );
		$options->offset = intval( $request->getVal( 'offset', '0' ) );

		if ( ( $value = $request->getVal( 'value', '' ) ) !== '' ) {
			$diWikiPages = $this->doQuerySubjectListWithValue( $value, $options );
		} else {
			$diWikiPages = $this->store->getAllPropertySubjects( $this->mProperty, $options );
		}

		if ( !$options->ascending ) {
			$diWikiPages = array_reverse( $diWikiPages );
		}

		$result = '';

		if ( count( $diWikiPages ) > 0 ) {
			$pageLister = new SMWPageLister( $diWikiPages, null, $this->limit, $this->from, $this->until );

			$this->mTitle->setFragment( '#SMWResults' ); // Make navigation point to the result list.
			$navigation = $pageLister->getNavigationLinks( $this->mTitle );

			$dvWikiPage = DataValueFactory::getInstance()->newDataValueByItem(
				$this->mProperty
			);

			// Allow the DV formatter to access a specific language code
			$dvWikiPage->setOption(
				DataValue::OPT_USER_LANGUAGE,
				Localizer::getInstance()->getUserLanguage()->getCode()
			);

			$titleText = htmlspecialchars( $dvWikiPage->getWikiValue() );
			$resultNumber = min( $this->limit, count( $diWikiPages ) );

			$result .= "<a name=\"SMWResults\"></a><div id=\"mw-pages\">\n" .
			           '<h2>' . wfMessage( 'smw_attribute_header', $titleText )->text() . "</h2>\n<p>";

			$result .= $this->getNavigationLinks( 'smw_attributearticlecount', $diWikiPages, $smwgPropertyPagingLimit ) .
			           $this->subjectObjectList( $diWikiPages ) . "\n</div>";
		}

		return $result;
	}

	/**
	 * Format $diWikiPages chunked by letter in a table that shows subject
	 * articles in one column and object articles/values in the other one.
	 *
	 * @param $diWikiPages array
	 * @return string
	 */
	protected function subjectObjectList( array $diWikiPages ) {
		global $wgContLang, $smwgMaxPropertyValues;

		$ac = count( $diWikiPages );

		if ( $ac > $this->limit ) {
			if ( $this->until !== '' ) {
				$start = 1;
			} else {
				$start = 0;
				$ac = $ac - 1;
			}
		} else {
			$start = 0;
		}

		$r = '<table class="property-page-results" style="width: 100%;" cellspacing="0" cellpadding="0">';
		$prev_start_char = 'None';

		for ( $index = $start; $index < $ac; $index++ ) {
			$diWikiPage = $diWikiPages[$index];
			$dvWikiPage = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPage, null );

			$sortkey = $this->store->getWikiPageSortKey( $diWikiPage );
			$start_char = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );

			// Header for index letters
			if ( $start_char != $prev_start_char ) {
				$r .= '<tr class="header-row" ><th class="smwpropname"><div class="header-title">' . htmlspecialchars( $start_char ) . "</div></th><th></th></tr>\n";
				$prev_start_char = $start_char;
			}

			// Property name
			$searchlink = SMWInfolink::newBrowsingLink( '+', $dvWikiPage->getWikiValue() );
			$r .= '<tr class="value-row" ><td class="smwpropname">' . $dvWikiPage->getShortHTMLText( smwfGetLinker() ) .
			      '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . '</td><td class="smwprops">';

			// Property values
			$ropts = new RequestOptions();
			$ropts->limit = $smwgMaxPropertyValues + 1;
			$values = $this->store->getPropertyValues( $diWikiPage, $this->mProperty, $ropts );
			$i = 0;

			foreach ( $values as $di ) {
				if ( $i != 0 ) {
					$r .= ', ';
				}
				$i++;

				if ( $i < $smwgMaxPropertyValues + 1 ) {
					$dv = DataValueFactory::getInstance()->newDataValueByItem( $di, $this->mProperty );

					$dv->setOutputFormat( 'LOCL' );

					$r .= $dv->getShortHTMLText( smwfGetLinker() ) . $dv->getInfolinkText( SMW_OUTPUT_HTML, smwfGetLinker() );
				} else {
					$searchlink = SMWInfolink::newInversePropertySearchLink( '…', $dvWikiPage->getWikiValue(), $this->mTitle->getText() );
					$r .= $searchlink->getHTML( smwfGetLinker() );
				}
			}

			$r .= "</td></tr>\n";
		}

		$r .= '</table>';

		return $r;
	}

	private function doQuerySubjectListWithValue( $value, $options ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$dataValue = $applicationFactory->getDataValueFactory()->newDataValueByProperty( $this->mProperty );
		$dataValue->setOption( DataValue::OPT_QUERY_CONTEXT, true );
		$dataValue->setUserValue( $value );
		$queryFactory = $applicationFactory->getQueryFactory();

		$description = $queryFactory->newDescriptionFactory()->newFromDataValue(
			$dataValue
		);

		$query = $queryFactory->newQuery( $description );
		$query->setLimit( $options->limit );
		$query->setOffset( $options->offset );
		$query->setSortKeys( array( '' => 'asc' ) );

		return $this->store->getQueryResult( $query )->getResults();
	}

	private function getSemanticData() {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $this->getPage()->getRevision() === null ) {
			return null;
		}

		$editInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newEditInfoProvider(
			$this->getPage(),
			$this->getPage()->getRevision()
		);

		return $editInfoProvider->fetchSemanticData();
	}

}
