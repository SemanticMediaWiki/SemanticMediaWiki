<?php

/**
 * Deprecated entry point for enabling Semantic MediaWiki.
 *
 * This file is loaded via Composer autoload so that the function is available
 * during LocalSettings.php execution, before the extension callback runs.
 *
 * @deprecated since 7.0.0. Use wfLoadExtension( 'SemanticMediaWiki' ) instead.
 *
 * @param mixed $namespace
 * @param bool $complete
 */
// phpcs:ignore MediaWiki.NamingConventions.PrefixedGlobalFunctions.allowedPrefix
function enableSemantics( $namespace = null, $complete = false ): void {
	if ( function_exists( 'wfDeprecated' ) ) {
		wfDeprecated( __FUNCTION__, '7.0.0' );
	}
}
