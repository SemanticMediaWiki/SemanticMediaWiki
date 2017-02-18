<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SpecialPage;
use SMW\Message;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\MediaWiki\Specials\PropertyLabelSimilarity\ContentsBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialPropertyLabelSimilarity extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'PropertyLabelSimilarity' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$this->setHeaders();
		$output = $this->getOutput();
		$webRequest = $this->getRequest();

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );

		$propertyLabelSimilarityLookup = new PropertyLabelSimilarityLookup(
			$store
		);

		$propertyLabelSimilarityLookup->setExemptionProperty(
			$applicationFactory->getSettings()->get( 'smwgSimilarityLookupExemptionProperty' )
		);

		$htmlFormRenderer = $applicationFactory->newMwCollaboratorFactory()->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$contentsBuilder = new ContentsBuilder(
			$propertyLabelSimilarityLookup,
			$htmlFormRenderer
		);

		$threshold = (int)$webRequest->getText( 'threshold', 90 );
		$type = $webRequest->getText( 'type', false );

		$offset = (int)$webRequest->getText( 'offset', 0 );
		$limit = (int)$webRequest->getText( 'limit', 50 );

		$requestOptions = $applicationFactory->getQueryFactory()->newRequestOptions();
		$requestOptions->setLimit( $limit );
		$requestOptions->setOffset( $offset );

		$requestOptions->addExtraCondition(
			array(
				'type' => $type,
				'threshold' => $threshold
			)
		);

		$output->addHtml(
			$contentsBuilder->getHtml( $requestOptions )
		);

		return true;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group';
	}

}
