<?php

namespace SMW\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SearchByProperty\PageBuilder;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;

use SMW\MediaWiki\MessageBuilder;
use SMW\Application;

use SpecialPage;

/**
 * A special page to search for entities that have a certain property with
 * a certain value.
 *
 * This special page for Semantic MediaWiki implements a view on a
 * relation-object pair,i.e. a typed backlink. For example, it shows me all
 * persons born in Croatia, or all winners of the Academy Award for best actress.
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class SpecialSearchByProperty extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'SearchByProperty' );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$output = $this->getOutput();

		$output->setPageTitle( $this->msg( 'searchbyproperty' )->text() );
		$output->addModules( 'ext.smw.tooltip' );
		$output->addModules( 'ext.smw.property' );

		list( $limit, $offset ) = $this->getLimitOffset();

		$application = Application::getInstance();

		$requestOptions = array(
			'limit'    => $limit,
			'offset'   => $offset,
			'property' => $this->getRequest()->getVal( 'property' ),
			'value'    => $this->getRequest()->getVal( 'value' ),
			'nearbySearchForType' => $application->getSettings()->get( 'smwgSearchByPropertyFuzzy' )
		);

		$htmlFormBuilder = $application->newHtmlFormBuilder(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$pageBuilder = new PageBuilder(
			$htmlFormBuilder,
			new PageRequestOptions( $query, $requestOptions ),
			new QueryResultLookup( $application->getStore() )
		);

		$output->addHTML( $pageBuilder->getHtml() );
	}

	/**
	 * FIXME MW 1.24 wfCheckLimits was deprecated in MediaWiki 1.24
	 */
	private function getLimitOffset() {

		if ( method_exists( $this->getRequest(), 'getLimitOffset' ) ) {
			return $this->getRequest()->getLimitOffset();
		}

		return wfCheckLimits();
	}

}
