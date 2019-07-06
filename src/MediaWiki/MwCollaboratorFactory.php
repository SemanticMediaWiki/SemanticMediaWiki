<?php

namespace SMW\MediaWiki;

use Language;
use Parser;
use Revision;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Renderer\HtmlColumnListRenderer;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Renderer\HtmlTableRenderer;
use SMW\MediaWiki\Renderer\HtmlTemplateRenderer;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use StripState;
use Title;
use User;
use WikiPage;

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
	 * @return MagicWordsFinder
	 */
	public function newMagicWordsFinder() {
		return ApplicationFactory::getInstance()->create( 'MagicWordsFinder' );
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
	 * @return HtmlFormRenderer
	 */
	public function newHtmlFormRenderer( Title $title, Language $language = null ) {

		if ( $language === null ) {
			$language = $title->getPageLanguage();
		}

		$messageBuilder = $this->newMessageBuilder( $language );

		return new HtmlFormRenderer( $title, $messageBuilder );
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlTableRenderer
	 */
	public function newHtmlTableRenderer() {
		return new HtmlTableRenderer();
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function newHtmlColumnListRenderer() {
		return new HtmlColumnListRenderer();
	}

	/**
	 * @since 2.1
	 *
	 * @return LoadBalancerConnectionProvider
	 */
	public function newLoadBalancerConnectionProvider( $connectionType, $asConnectionRef = true ) {

		$loadBalancerConnectionProvider = new LoadBalancerConnectionProvider(
			$connectionType
		);

		$loadBalancerConnectionProvider->asConnectionRef(
			$asConnectionRef
		);

		return $loadBalancerConnectionProvider;
	}

	/**
	 * @since 2.1
	 *
	 * @param string|null $provider
	 *
	 * @return ConnectionProvider
	 */
	public function newConnectionProvider( $provider = null ) {

		$connectionProvider = new ConnectionProvider(
			$provider
		);

		$connectionProvider->setLocalConnectionConf(
			$this->applicationFactory->getSettings()->get( 'smwgLocalConnectionConf' )
		);

		$connectionProvider->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		return $connectionProvider;
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
	 * @deprecated since 3.1
	 * @since 2.5
	 *
	 * @param WikiPage $wkiPage
	 * @param Revision $revision
	 * @param User|null $user
	 *
	 * @return EditInfo
	 */
	public function newEditInfoProvider( WikiPage $wkiPage, Revision $revision, User $user = null ) {
		return $this->newEditInfo( $wkiPage, $revision, $user );
	}

	/**
	 * @since 2.5
	 *
	 * @param WikiPage $wkiPage
	 * @param Revision $revision
	 * @param User|null $user
	 *
	 * @return EditInfo
	 */
	public function newEditInfo( WikiPage $wkiPage, Revision $revision, User $user = null ) {
		return new EditInfo( $wkiPage, $revision, $user );
	}

	/**
	 * @since 2.2
	 *
	 * @return WikitextTemplateRenderer
	 */
	public function newWikitextTemplateRenderer() {
		return new WikitextTemplateRenderer();
	}

	/**
	 * @since 2.2
	 *
	 * @param Parser $parser
	 *
	 * @return HtmlTemplateRenderer
	 */
	public function newHtmlTemplateRenderer( Parser $parser ) {
		return new HtmlTemplateRenderer(
			$this->newWikitextTemplateRenderer(),
			$parser
		);
	}

	/**
	 * @since 2.2
	 *
	 * @return MediaWikiNsContentReader
	 */
	public function newMediaWikiNsContentReader() {
		return new MediaWikiNsContentReader();
	}

	/**
	 * @since 3.0
	 *
	 * @param StripState $stripState
	 *
	 * @return StripMarkerDecoder
	 */
	public function newStripMarkerDecoder( StripState $stripState ) {

		$stripMarkerDecoder = new StripMarkerDecoder(
			$stripState
		);

		$stripMarkerDecoder->isSupported(
			$this->applicationFactory->getSettings()->isFlagSet( 'smwgParserFeatures', SMW_PARSER_UNSTRIP )
		);

		return $stripMarkerDecoder;
	}

}
