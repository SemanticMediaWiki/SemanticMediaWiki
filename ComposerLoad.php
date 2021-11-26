<?php

/**
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3984
 *
 * To avoid issues with redeclaring the `SemanticMediaWiki` class and still provide
 * access to the in-vendor registration, use this file as entry point to ensure
 * functions and classes can be registered from within or outside the vendor.
 */
require_once __DIR__ . "/SemanticMediaWiki.php";
