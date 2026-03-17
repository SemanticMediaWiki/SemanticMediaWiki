<?php

/**
 * This file is a back-compat shim and is not used by SMW.
 *
 * If you would like to see the DefaultSettings.php that was here before, look in
 * src/DefaultSettings.php.
 *
 * @deprecated 4.0.0
 * @codeCoverageIgnore
 */
require_once __DIR__ . "/src/SemanticMediaWiki.php";
return SemanticMediaWiki::getDefaultSettings();
