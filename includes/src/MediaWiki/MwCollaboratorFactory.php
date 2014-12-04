<?php

namespace SMW\MediaWiki;

use SMW\ApplicationFactory;

use Title;
use Language;
use WikiPage;
use Revision;
use User;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class MwCollaboratorFactory {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 2.1
	 *
	 * @param ApplicationFactory $applicationFactory
	 */
	public function __construct( ApplicationFactory $applicationFactory ) {
		$this->applicationFactory = $applicationFactory;
	}

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 *
	 * @return JobQueueLookup
	 */
	public function newJobQueueLookup( Database $connection ) {
		return new JobQueueLookup( $connection );
	}

	/**
	 * @since 2.1
	 *
	 * @param Language|null $language
	 *
	 * @return MessageBuilder
	 */
	public function newMessageBuilder( Language $language = null ) {
		return new MessageBuilder( $language );
	}

	/**
	 * @since 2.1
	 *
	 * @return MagicWordFinder
	 */
	public function newMagicWordFinder() {
		return new MagicWordFinder();
	}

	/**
	 * @since 2.1
	 *
	 * @return RedirectTargetFinder
	 */
	public function newRedirectTargetFinder() {
		return new RedirectTargetFinder();
	}

	/**
	 * @since 2.1
	 *
	 * @return DeepRedirectTargetResolver
	 */
	public function newDeepRedirectTargetResolver() {
		return new DeepRedirectTargetResolver( $this->applicationFactory->newPageCreator() );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 * @param Language|null $language
	 *
	 * @return HtmlFormBuilder
	 */
	public function newHtmlFormBuilder( Title $title, Language $language = null ) {

		if ( $language === null ) {
			$language = $title->getPageLanguage();
		}

		$messageBuilder = $this->newMessageBuilder( $language );

		return new HtmlFormBuilder( $title, $messageBuilder );
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlTableBuilder
	 */
	public function newHtmlTableBuilder() {
		return new HtmlTableBuilder();
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlColumnListFormatter
	 */
	public function newHtmlColumnListFormatter() {
		return new HtmlColumnListFormatter();
	}

	/**
	 * @since 2.1
	 *
	 * @return LazyDBConnectionProvider
	 */
	public function newLazyDBConnectionProvider( $connectionType ) {
		return new LazyDBConnectionProvider( $connectionType );
	}

	/**
	 * @since 2.1
	 *
	 * @return DatabaseConnectionProvider
	 */
	public function newMediaWikiDatabaseConnectionProvider() {
		return new DatabaseConnectionProvider();
	}

	/**
	 * @since 2.0
	 *
	 * @param WikiPage $wkiPage
	 * @param Revision|null $revision
	 * @param User|null $user
	 *
	 * @return PageInfoProvider
	 */
	public function newPageInfoProvider( WikiPage $wkiPage, Revision $revision = null, User $user = null ) {
		return new PageInfoProvider( $wkiPage, $revision, $user );
	}

	/**
	 * @since 2.1
	 *
	 * @return PageUpdater
	 */
	public function newPageUpdater() {
		return new PageUpdater();
	}

}
