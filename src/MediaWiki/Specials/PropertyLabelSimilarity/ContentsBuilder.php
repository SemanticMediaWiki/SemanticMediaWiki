<?php

namespace SMW\MediaWiki\Specials\PropertyLabelSimilarity;

use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class ContentsBuilder {

	/**
	 * @var PropertyLabelSimilarityLookup
	 */
	private $propertyLabelSimilarityLookup;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @since 2.5
	 *
	 * @param PropertyLabelSimilarityLookup $propertyLabelSimilarityLookup
	 * @param HtmlFormRenderer $htmlFormRenderer
	 */
	public function __construct( PropertyLabelSimilarityLookup $propertyLabelSimilarityLookup, HtmlFormRenderer $htmlFormRenderer ) {
		$this->propertyLabelSimilarityLookup = $propertyLabelSimilarityLookup;
		$this->htmlFormRenderer = $htmlFormRenderer;
	}

	/**
	 * @since 2.5
	 *
	 * @param RequestOptions $requestOption
	 */
	public function getHtml( RequestOptions $requestOptions ) {

		$threshold = 90;
		$type = '';

		foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
			if ( isset( $extraCondition['type'] ) ) {
				$type = $extraCondition['type'];
			}

			if ( isset( $extraCondition['threshold'] ) ) {
				$threshold = $extraCondition['threshold'];
			}
		}

		$this->propertyLabelSimilarityLookup->setThreshold(
			$threshold
		);

		$result = $this->propertyLabelSimilarityLookup->compareAndFindLabels(
			$requestOptions
		);

		$html = $this->getForm(
			$requestOptions->getLimit(),
			$requestOptions->getOffset(),
			count( $result ),
			$threshold,
			$type
		);

		if ( $result !== array() ) {
			 $html .= '<pre>' . json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</pre>';
		} else {
			 $html .= $this->getMessageAsString( 'smw-property-label-similarity-noresult' );
		}

		return $html;
	}

	private function getForm( $limit, $offset, $resultCount, $threshold, $type ) {

		$exemptionProperty = $this->propertyLabelSimilarityLookup->getExemptionProperty();
		$lookupCount = $this->propertyLabelSimilarityLookup->getLookupCount();

		// Allow for an extra range since the property pool may be larger than
		// the reductive comparison matches, +1 is to request additional paging
		if ( $limit + $offset < $this->propertyLabelSimilarityLookup->getPropertyMaxCount() ) {
			$lookupCount = $limit + $offset + 1;
		}

		$html = $this->getMessageAsString(
			array( 'smw-property-label-similarity-docu', $exemptionProperty ),
			Message::PARSE
		);

		$html .= $this->htmlFormRenderer
			->setName( 'smw-property-label-similarity-title' )
			->setMethod( 'get' )
			->withFieldset()
			->addPaging(
				$limit,
				$offset,
				$lookupCount,
				$resultCount )
			->addHiddenField( 'limit', $limit )
			->addHiddenField( 'offset', $offset )
			->addInputField(
				$this->getMessageAsString( 'smw-property-label-similarity-threshold' ),
				'threshold',
				$threshold,
				'',
				5
			)
			->addNonBreakingSpace()
			->addCheckbox(
				$this->getMessageAsString( 'smw-property-label-similarity-type' ),
				'type',
				'yes',
				$type === 'yes',
				null,
				array(
					'style' => 'float:right'
				)
			)
			->addQueryParameter( 'type', $type )
			->addSubmitButton( $this->getMessageAsString( 'allpagessubmit' ) )
			->getForm();

		return Html::rawElement( 'div', array( 'class' => 'plainlinks'), $html ) . Html::element( 'p', array(), '' );
	}

	private function getMessageAsString( $parameters, $type = Message::TEXT ) {
		return Message::get( $parameters, $type, Message::USER_LANGUAGE );
	}

}
