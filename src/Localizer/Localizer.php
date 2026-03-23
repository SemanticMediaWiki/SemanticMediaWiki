<?php

namespace SMW\Localizer;

use Exception;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Language\LanguageCode;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use SMW\DataItems\WikiPage;
use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\MediaWiki\ExtendedDateTime;
use SMW\MediaWiki\LocalTime;
use SMW\NamespaceManager;
use SMW\Services\ServicesFactory;
use SMW\Site;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class Localizer {

	private static ?Localizer $instance = null;

	/**
	 * @since 2.1
	 */
	public function __construct(
		private readonly Language $contentLanguage,
		private readonly NamespaceInfo $namespaceInfo,
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly IContextSource $context,
	) {
	}

	/**
	 * @since 2.1
	 *
	 * @return Localizer
	 */
	public static function getInstance(): Localizer {
		if ( self::$instance !== null ) {
			return self::$instance;
		}

		$servicesFactory = ServicesFactory::getInstance();

		self::$instance = new self(
			$servicesFactory->singleton( 'ContentLanguage' ),
			MediaWikiServices::getInstance()->getNamespaceInfo(),
			$servicesFactory->singleton( 'UserOptionsLookup' ),
			RequestContext::getMain()
		);

		return self::$instance;
	}

	/**
	 * @since 2.1
	 */
	public static function clear(): void {
		self::$instance = null;
	}

	/**
	 * @since 2.1
	 *
	 * @return Language
	 */
	public function getContentLanguage(): Language {
		return $this->contentLanguage;
	}

	/**
	 * @since 2.4
	 *
	 * @return Language
	 */
	public function getUserLanguage() {
		return $GLOBALS['wgLang'];
	}

	/**
	 * @since 3.0
	 *
	 * @param User|null $user
	 *
	 * @return bool
	 */
	public function hasLocalTimeOffsetPreference( $user = null ) {
		if ( !$user instanceof User ) {
			$user = $this->context->getUser();
		}

		return $this->userOptionsLookup->getOption( $user, 'smw-prefs-general-options-time-correction' );
	}

	/**
	 * @since 3.0
	 *
	 * @param ExtendedDateTime $dateTime
	 * @param User|null $user
	 *
	 * @return ExtendedDateTime
	 */
	public function getLocalTime( ExtendedDateTime $dateTime, $user = null ): ExtendedDateTime {
		if ( !$user instanceof User ) {
			$user = $this->context->getUser();
		}

		LocalTime::setLocalTimeOffset(
			$GLOBALS['wgLocalTZoffset']
		);

		$timeCorrection = $this->userOptionsLookup->getOption( $user, 'timecorrection' );
		return LocalTime::getLocalizedTime( $dateTime, $timeCorrection );
	}

	/**
	 * @note
	 *
	 * 1. If the page content language is available use it as preferred language
	 * (as it is clear that the page content was intended to be in a specific
	 * language)
	 * 2. If no page content language was assigned use the global content
	 * language
	 *
	 * General rules:
	 * - Special pages are in the user language
	 * - Display of values (DV) should use the user language if available otherwise
	 * use the content language as fallback
	 * - Storage of values (DI) should always use the content language
	 *
	 * Notes:
	 * - The page content language is the language in which the content of a page is
	 * written in wikitext
	 *
	 * @since 2.4
	 *
	 * @param WikiPage|Title|null $title
	 *
	 * @return Language
	 */
	public function getPreferredContentLanguage( $title = null ): Language {
		$language = '';

		if ( $title instanceof WikiPage ) {
			$title = $title->getTitle();
		}

		// If the page language is different from the global content language
		// then we assume that an explicit language object was given otherwise
		// the Title is using the content language as fallback
		if ( $title instanceof Title ) {

			// Avoid "MWUnknownContentModelException ... " when content model
			// is not registered
			try {
				$language = $title->getPageLanguage();
			} catch ( Exception $e ) {

			}
		}

		return $language instanceof Language ? $language : $this->getContentLanguage();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return Language
	 */
	public function getLanguage( $languageCode = '' ): Language {
		if ( $languageCode === '' || !$languageCode || $languageCode === null ) {
			return $this->getContentLanguage();
		}

		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		return $languageFactory->getLanguage( $languageCode );
	}

	/**
	 * @since 2.4
	 *
	 * @param Language|string $language
	 *
	 * @return LocalLanguage
	 */
	public function getLang( $language = '' ): LocalLanguage {
		$languageCode = $language;

		if ( $language instanceof Language ) {
			$languageCode = $language->getCode();
		}

		if ( $languageCode === '' || !$languageCode || $languageCode === null ) {
			$languageCode = $this->getContentLanguage()->getCode();
		}

		return LocalLanguage::getInstance()->fetch( $languageCode );
	}

	/**
	 * @since 2.1
	 *
	 * @param int $index
	 *
	 * @return string
	 */
	public function getNsText( $index ): string {
		return str_replace( '_', ' ', $this->contentLanguage->getNsText( $index ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $index
	 *
	 * @return string
	 */
	public function getCanonicalNamespaceTextById( $index ) {
		$canonicalNames = NamespaceManager::getCanonicalNames();

		if ( isset( $canonicalNames[$index] ) ) {
			return $canonicalNames[$index];
		}

		return $this->namespaceInfo->getCanonicalName( $index );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $namespaceName
	 *
	 * @return int|bool
	 */
	public function getNsIndex( $namespaceName ) {
		return $this->contentLanguage->getNsIndex( str_replace( ' ', '_', $namespaceName ) );
	}

	/**
	 * Convert a namespace index to a string in the preferred variant
	 *
	 * @since 3.2
	 *
	 * @deprecated since 1.35 use LanguageConverter::convertNamespace instead
	 *
	 * @param int $ns namespace
	 * @param string|null $variant
	 *
	 * @return string a string representation of the namespace
	 */
	public function convertNamespace( $ns, $variant = null ): string {
		$services = MediaWikiServices::getInstance();
		$langConverter = $services->getLanguageConverterFactory()->getLanguageConverter( $this->contentLanguage );
		return $langConverter->convertNamespace( $ns, $variant );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public static function isKnownLanguageTag( $languageCode ): bool {
		$languageCode = mb_strtolower( $languageCode );
		$languageNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();

		return $languageNameUtils->isKnownLanguageTag( $languageCode );
	}

	/**
	 * @see IETF language tag / BCP 47 standards
	 *
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public static function asBCP47FormattedLanguageCode( $languageCode ) {
		return LanguageCode::bcp47( $languageCode );
	}

	/**
	 * @deprecated 2.5, use Localizer::getAnnotatedLanguageCodeFrom instead
	 * @since 2.4
	 *
	 * @param string &$value
	 *
	 * @return string|false
	 */
	public static function getLanguageCodeFrom( &$value ): string|false {
		return self::getAnnotatedLanguageCodeFrom( $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $index
	 * @param string $text
	 *
	 * @return string
	 */
	public function createTextWithNamespacePrefix( $index, string $text ): string {
		return $this->getNsText( $index ) . ':' . $text;
	}

	/**
	 * @since 2.5
	 *
	 * @param int $index
	 * @param string $url
	 *
	 * @return string
	 */
	public function getCanonicalizedUrlByNamespace( $index, $url ): string {
		$namespace = $this->getNsText( $index );

		if ( strpos( $url, 'title=' ) !== false ) {
			return str_replace(
				[
					'title=' . wfUrlencode( $namespace ) . ':',
					'title=' . $namespace . ':'
				],
				'title=' . $this->getCanonicalNamespaceTextById( $index ) . ':',
				$url
			);
		}

		return str_replace(
			[
				wfUrlencode( '/' . $namespace . ':' ),
				'/' . $namespace . ':'
			],
			'/' . $this->getCanonicalNamespaceTextById( $index ) . ':',
			$url
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param string &$value
	 *
	 * @return string|false
	 */
	public static function getAnnotatedLanguageCodeFrom( &$value ): false|string {
		if ( strpos( $value, '@' ) === false ) {
			return false;
		}

		if ( ( $langCode = mb_substr( strrchr( $value, "@" ), 1 ) ) !== '' ) {
			$value = str_replace( '_', ' ', substr_replace( $value, '', ( mb_strlen( $langCode ) + 1 ) * -1 ) );
		}

		// Do we want to check here whether isKnownLanguageTag or not?
		if ( $langCode !== '' && ctype_alpha( str_replace( [ '-' ], '', $langCode ) ) ) {
			return $langCode;
		}

		return false;
	}

	/**
	 * Takes a text and turns it into a normalised version. This function
	 * reimplements the title normalization as done in Title.php in order to
	 * achieve conversion with less overhead. The official code could be called
	 * here if more advanced normalization is needed.
	 *
	 * @since 3.2
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function normalizeTitleText( string $text ): string {
		$text = trim( $text );

		if ( Site::isCapitalLinks() ) {
			$text = $this->contentLanguage->ucfirst( $text );
		}

		// https://www.mediawiki.org/wiki/Manual:Page_title
		// Titles beginning or ending with a space (underscore), or containing two
		// or more consecutive spaces (underscores).
		return str_replace( [ '__', '_', '  ' ], ' ', $text );
	}

	/**
	 * @see Language::convertDoubleWidth
	 *
	 * Convert double-width roman characters to single-width.
	 * range: ff00-ff5f ~= 0020-007f
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function convertDoubleWidth( $string ): string {
		static $full = null;
		static $half = null;

		if ( $full === null ) {
			$fullWidth = "０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
			$halfWidth = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

			// http://php.net/manual/en/function.str-split.php, mb_str_split
			$length = mb_strlen( $fullWidth, "UTF-8" );
			$full = [];

			for ( $i = 0; $i < $length; $i += 1 ) {
				$full[] = mb_substr( $fullWidth, $i, 1, "UTF-8" );
			}

			$half = str_split( $halfWidth );
		}

		return str_replace( $full, $half, trim( $string ) );
	}

}
