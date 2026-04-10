<?php

namespace SMW\MediaWiki;

use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\StripState;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\MediaWiki\Renderer\HtmlColumnListRenderer;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Renderer\HtmlTableRenderer;
use SMW\MediaWiki\Renderer\HtmlTemplateRenderer;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class MwCollaboratorFactory {

	/**
	 * @since 2.1
	 */
	public function __construct( private readonly ApplicationFactory $applicationFactory ) {
	}

	/**
	 * @since 2.1
	 *
	 * @param Language|null $language
	 *
	 * @return MessageBuilder
	 */
	public function newMessageBuilder( ?Language $language = null ): MessageBuilder {
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
	public function newRedirectTargetFinder(): RedirectTargetFinder {
		return new RedirectTargetFinder();
	}

	/**
	 * @since 2.1
	 *
	 * @return DeepRedirectTargetResolver
	 */
	public function newDeepRedirectTargetResolver(): DeepRedirectTargetResolver {
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
	public function newHtmlFormRenderer( Title $title, ?Language $language = null ): HtmlFormRenderer {
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
	public function newHtmlTableRenderer(): HtmlTableRenderer {
		return new HtmlTableRenderer();
	}

	/**
	 * @since 2.1
	 *
	 * @return HtmlColumnListRenderer
	 */
	public function newHtmlColumnListRenderer(): HtmlColumnListRenderer {
		return new HtmlColumnListRenderer();
	}

	/**
	 * @since 2.1
	 *
	 * @param int $connectionType
	 * @param bool $asConnectionRef Deprecated parameter since 5.0
	 *
	 * @note The parameter $asConnectionRef is deprecated since 5.0
	 *
	 * @return LoadBalancerConnectionProvider
	 */
	public function newLoadBalancerConnectionProvider( $connectionType, $asConnectionRef = true ): LoadBalancerConnectionProvider {
		$loadBalancerConnectionProvider = new LoadBalancerConnectionProvider(
			$connectionType
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
	public function newConnectionProvider( $provider = null ): ConnectionProvider {
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
	 * @param WikiPage $wikiPage
	 * @param ?RevisionRecord $revision
	 * @param ?User $user
	 * @param ?bool $isReUpload
	 *
	 * @return PageInfoProvider
	 */
	public function newPageInfoProvider(
		WikiPage $wikiPage,
		?RevisionRecord $revision = null,
		?User $user = null,
		?bool $isReUpload = null
	): PageInfoProvider {
		$pageInfoProvider = new PageInfoProvider( $wikiPage, $revision, $user, $isReUpload );

		$pageInfoProvider->setRevisionGuard(
			$this->applicationFactory->singleton( 'RevisionGuard' )
		);

		$pageInfoProvider->setRevisionLookup(
			$this->applicationFactory->singleton( 'RevisionLookup' )
		);

		return $pageInfoProvider;
	}

	/**
	 * @deprecated since 3.1
	 * @since 2.5
	 *
	 * @param WikiPage $wikiPage
	 * @param RevisionRecord $revision
	 * @param ?User $user
	 *
	 * @return EditInfo
	 */
	public function newEditInfoProvider(
		WikiPage $wikiPage,
		RevisionRecord $revision,
		?User $user = null
	): EditInfo {
		return $this->newEditInfo( $wikiPage, $revision, $user );
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $wikiPage
	 * @param ?RevisionRecord $revision
	 * @param ?User $user
	 *
	 * @return EditInfo
	 */
	public function newEditInfo(
		WikiPage $wikiPage,
		?RevisionRecord $revision = null,
		?User $user = null
	): EditInfo {
		if ( $user === null ) {
			$user = RequestContext::getMain()->getUser();
		}

		$editInfo = new EditInfo( $wikiPage, $revision, $user );

		$editInfo->setRevisionGuard(
			$this->applicationFactory->singleton( 'RevisionGuard' )
		);

		return $editInfo;
	}

	/**
	 * @since 2.2
	 *
	 * @return WikitextTemplateRenderer
	 */
	public function newWikitextTemplateRenderer(): WikitextTemplateRenderer {
		return new WikitextTemplateRenderer();
	}

	/**
	 * @since 2.2
	 *
	 * @param Parser $parser
	 *
	 * @return HtmlTemplateRenderer
	 */
	public function newHtmlTemplateRenderer( Parser $parser ): HtmlTemplateRenderer {
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
		return $this->applicationFactory->create( 'MediaWikiNsContentReader' );
	}

	/**
	 * @since 3.0
	 *
	 * @param StripState $stripState
	 *
	 * @return StripMarkerDecoder
	 */
	public function newStripMarkerDecoder( StripState $stripState ): StripMarkerDecoder {
		$stripMarkerDecoder = new StripMarkerDecoder(
			$stripState
		);

		$stripMarkerDecoder->isSupported(
			$this->applicationFactory->getSettings()->isFlagSet( 'smwgParserFeatures', SMW_PARSER_UNSTRIP )
		);

		return $stripMarkerDecoder;
	}

}
