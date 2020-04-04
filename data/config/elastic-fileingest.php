<?php

/**
 * Convenience settings to extend Semantic MediaWiki and support file ingestion
 * in connection with the ElasticStore.
 *
 * @since 3.2
 */

$extraSettings = [
	'settings' => [
		'data' => [ "index.mapping.total_fields.limit" => 12000 ]
	],
	'indexer' => [
		"raw.text" => true,
		"experimental.file.ingest" => true,
		"throw.exception.on.illegal.argument.error" => false
	],
	"query" => [
		"highlight.fragment" => [ "type" => "unified" ]
	]
];

return [

	/**
	 * @see $smwgDefaultStore
	 */
	'smwgDefaultStore' => 'SMWElasticStore',

	/**
	 * @see $smwgElasticsearchConfig
	 */
	'smwgElasticsearchConfig' => array_replace_recursive( $GLOBALS['smwgElasticsearchConfig'], $extraSettings ),
];