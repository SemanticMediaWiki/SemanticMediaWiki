<?php

namespace SMW\Utils;

use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * @see https://www.semantic-mediawiki.org/wiki/SMW_logo
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class Logo {

	/**
	 * @since 3.1
	 */
	public static function get( string $key ): ?string {
		if ( $key === 'small' ) {
			return self::small();
		}

		if ( $key === 'footer' ) {
			return self::footer();
		}

		return null;
	}

	private static function small(): string {
		$extAssets = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( MainConfigNames::ExtensionAssetsPath );
		return "$extAssets/SemanticMediaWiki/res/smw/assets/logo_small.svg";
	}

	private static function footer(): string {
		$extAssets = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( MainConfigNames::ExtensionAssetsPath );
		return version_compare( MW_VERSION, '1.43', '>=' )
			? "$extAssets/SemanticMediaWiki/res/smw/assets/logo_footer.svg"
			: "$extAssets/SemanticMediaWiki/res/smw/assets/logo_footer_legacy.svg";
	}

}
