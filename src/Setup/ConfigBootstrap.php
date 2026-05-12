<?php

namespace SMW\Setup;

use SMW\SQLStore\EntityStore\EntityIdManager;

/**
 * Registration-time bootstrap for SMW config defaults that cannot be
 * expressed as static JSON in extension.json — settings whose values
 * derive from PHP constants (SMW_FACTBOX_*, NS_*, DB_REPLICA, etc.),
 * class constants (EntityIdManager::DEFAULT_CACHE_SIZES), or runtime paths
 * ($smwgIP).
 *
 * Called from SemanticMediaWiki::initExtension() after extension.json's
 * config defaults have been seeded into $GLOBALS by ExtensionRegistry.
 *
 * Two distinct merge contracts coexist here, applied per setting:
 *
 * - **provide-default** (scalar feature-flag / cache-type settings, e.g.
 *   smwgFactboxFeatures, smwgQFeatures, smwgMainCacheType). Guarded with
 *   `!isset($GLOBALS[$key])` so the default is only written when the user
 *   hasn't set the global. A user value of any kind — including `null`,
 *   `0`, `false` — wins.
 *
 * - **explicit per-strategy merge** (compound array settings, e.g.
 *   smwgNamespacesWithSemanticLinks, smwgElasticsearchConfig). Runs
 *   unconditionally so that *partial* writes from LocalSettings.php
 *   (e.g. `$smwgElasticsearchConfig['query']['x']['y'] = 'z';`) merge
 *   cleanly with the manifest defaults. Each compound block uses the
 *   merge form that mirrors the merge_strategy it would have declared if
 *   the value could be expressed in JSON: `+` (array_plus), the wfArrayPlus2d
 *   loop pattern, or `array_replace_recursive($defaults, $userValue)`.
 *   These blocks MUST stay unconditional — adding an `!isset()` guard
 *   would re-open the partial-write bug class behind #6649 and #6726.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigBootstrap {

	/**
	 * @since 7.0.0
	 */
	public static function seedComputedDefaults(): void {
		if ( !isset( $GLOBALS['smwgMainCacheType'] ) ) {
			$GLOBALS['smwgMainCacheType'] = CACHE_ANYTHING;
		}

		if ( !isset( $GLOBALS['smwgQueryResultCacheType'] ) ) {
			$GLOBALS['smwgQueryResultCacheType'] = CACHE_NONE;
		}

		if ( !isset( $GLOBALS['smwStrictComparators'] ) ) {
			// Legacy alias (no `g` after `smw`); see also smwgQStrictComparators
			// which is the canonical name in extension.json. Removed in a
			// future major version once consumers are migrated.
			$GLOBALS['smwStrictComparators'] = false;
		}

		// smwgLocalConnectionConf — array_plus_2d semantics: user inner keys win,
		// missing connections are filled from defaults.
		$defaults = [
			'mw.db' => [
				'read'  => DB_REPLICA,
				'write' => DB_PRIMARY,
			],
			'mw.db.queryengine' => [
				'read'  => DB_REPLICA,
				'write' => DB_PRIMARY,
			],
		];
		$user = $GLOBALS['smwgLocalConnectionConf'] ?? [];
		foreach ( $defaults as $name => $defaultPair ) {
			$userPair = $user[$name] ?? [];
			$user[$name] = $userPair + $defaultPair;
		}
		$GLOBALS['smwgLocalConnectionConf'] = $user;

		// smwgNamespacesWithSemanticLinks — array_plus semantics: user keys win,
		// standard MW + SMW custom namespaces filled from defaults. SMW NS
		// keys must be seeded here (not in the CanonicalNamespaces hook
		// handler) because Settings::loadFromGlobals() runs inside
		// wgExtensionFunctions, before any Title resolution fires the hook.
		$defaults = [
			NS_MAIN              => true,
			NS_TALK              => false,
			NS_USER              => true,
			NS_USER_TALK         => false,
			NS_PROJECT           => true,
			NS_PROJECT_TALK      => false,
			NS_FILE              => true,
			NS_FILE_TALK         => false,
			NS_MEDIAWIKI         => false,
			NS_MEDIAWIKI_TALK    => false,
			NS_TEMPLATE          => false,
			NS_TEMPLATE_TALK     => false,
			NS_HELP              => true,
			NS_HELP_TALK         => false,
			NS_CATEGORY          => true,
			NS_CATEGORY_TALK     => false,
			SMW_NS_PROPERTY      => true,
			SMW_NS_PROPERTY_TALK => false,
			SMW_NS_CONCEPT       => true,
			SMW_NS_CONCEPT_TALK  => false,
			SMW_NS_SCHEMA        => true,
			SMW_NS_SCHEMA_TALK   => false,
		];
		$GLOBALS['smwgNamespacesWithSemanticLinks'] = ( $GLOBALS['smwgNamespacesWithSemanticLinks'] ?? [] ) + $defaults;

		// smwgEntityCacheSizes — array_plus semantics: user-overridden pools win,
		// unset pools filled from EntityIdManager::DEFAULT_CACHE_SIZES.
		$GLOBALS['smwgEntityCacheSizes'] = ( $GLOBALS['smwgEntityCacheSizes'] ?? [] )
			+ EntityIdManager::DEFAULT_CACHE_SIZES;

		// smwgElasticsearchConfig — array_replace_recursive semantics: user values
		// at any depth win; unset keys at any depth survive from defaults.
		// The $smwgIP paths in index_def cannot be expressed as static JSON.
		$smwgIP = $GLOBALS['smwgIP'];
		$defaults = [
			'index_def' => [
				'data'   => $smwgIP . '/data/elastic/smw-data-standard.json',
				'lookup' => $smwgIP . '/data/elastic/smw-lookup.json',
			],
			'connection' => [
				'quick_ping'      => true,
				'retries'         => 2,
				'timeout'         => 30,
				'connect_timeout' => 30,
			],
			'settings' => [
				'data' => [
					'index.mapping.total_fields.limit' => 9000,
					'index.max_result_window'          => 50000,
				],
			],
			'indexer' => [
				'raw.text'                                  => false,
				'experimental.file.ingest'                  => false,
				'throw.exception.on.illegal.argument.error' => true,
				'job.recovery.retries'                      => 5,
				'job.file.ingest.retries'                   => 3,
				'monitor.entity.replication'                => true,
				'monitor.entity.replication.cache_lifetime' => 3600,
				'data.sqlstore_compatibility'               => true,
			],
			'query' => [
				'fallback.no_connection'    => false,
				'profiling'                 => false,
				'debug.explain'             => true,
				'debug.description.log'     => true,
				'maximum.value.length'      => 500,
				'must_not.property.exists'  => true,
				'sort.property.must.exists' => true,
				'score.sortfield'           => 'es.score',
				'query_string.boolean.operators' => true,
				'compat.mode'               => true,
				'subquery.size'             => 10000,
				'subquery.constant.score'   => true,
				'subquery.terms.lookup.result.size.index.write.threshold' => 200,
				'subquery.terms.lookup.cache.lifetime'                    => 60 * 60,
				'concept.terms.lookup'                                    => true,
				'concept.terms.lookup.result.size.index.write.threshold'  => 10,
				'concept.terms.lookup.cache.lifetime'                     => 60 * 60,
				'cjk.best.effort.proximity.match'       => true,
				'wide.proximity.as.match_phrase'        => true,
				'wide.proximity.fields'                 => [
					'subject.title^8',
					'text_copy^5',
					'text_raw',
					'attachment.title^3',
					'attachment.content',
				],
				'uri.field.case.insensitive'                  => false,
				'text.field.case.insensitive.eq.match'        => false,
				'page.field.case.insensitive.proximity.match' => true,
				'highlight.fragment'                          => [ 'number' => 1, 'size' => 250, 'type' => false ],
			],
		];
		$GLOBALS['smwgElasticsearchConfig'] = array_replace_recursive(
			$defaults,
			$GLOBALS['smwgElasticsearchConfig'] ?? []
		);
	}

}
