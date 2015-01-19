<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Jobs\DeleteSubjectJob;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDelete {

	/**
	 * @var Wikipage
	 */
	protected $wikiPage = null;

	/**
	 * @since  2.0
	 *
	 * @param Wikipage $wikiPage
	 */
	public function __construct( &$wikiPage, &$user, &$reason, &$error ) {
		$this->wikiPage = $wikiPage;
		$this->user = $user;
		$this->reason = $reason;
		$this->error = $error;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$deleteSubjectJob = new DeleteSubjectJob( $this->wikiPage->getTitle(), array(
			'asDeferredJob'  => $settings->get( 'smwgDeleteSubjectAsDeferredJob' ),
			'withAssociates' => $settings->get( 'smwgDeleteSubjectWithAssociatesRefresh' )
		) );

		$deleteSubjectJob->execute();

		return true;
	}

}
