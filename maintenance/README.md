# Maintenance scripts

Scripts are expected to be run using the PHP command line (if your MediaWiki is properly configured).

If you keep SMW in the standard directory `./extensions/SemanticMediaWiki` (below your MediaWiki installation) then you can run these scripts from almost anywhere.

Otherwise, it is required to set the environment variable `MW_INSTALL_PATH` to the root of your MediaWiki installation first. This is also required if you use a symbolic link from `./extensions/SemanticMediaWiki` to the actual installation directory of Semantic MediaWiki.

## Usage

Setting environment variables is different for different operating systems and shells, but can normally be done from the command line right before the php call. On Bash (Linux), e.g. one can use the following call to execute "setupStore.php" with a different MediaWiki location:

```
export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php
```
In some setups that use a lot of shared code for many wikis, it might be
required to specify the location of "LocalSettings.php" explicitly, too:

```
export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php --conf=/path/to/mediawiki/LocalSettings.php
```

### dumpRDF.php

Complete RDF export of existing triples.

Usage:
- php dumpRDF.php
- [--categories|--classes|--concepts|--conf|--d|--dbpass|--dbuser|--e|--file|--globals|--help|--individuals|--memory-limit|--page|--profiler|--properties|--quiet|--server|--types|--wiki]

### populateHashField.php

Populate the `smw_hash` field for all entities that have a missing entry.

Usage:
- php populateHashField.php
- [--conf|--dbpass|--dbuser|--globals|--help|--memory-limit|--profiler|--quiet|--server|--wiki]

### rebuildConceptCache.php

Manages concept caches in Semantic MediaWiki.

Usage:
- php rebuildConceptCache.php
- [--concept|--conf|--create|--dbpass|--dbuser|--debug|--delete|--e|--globals|--hard|--help|--memory-limit|--old|--profiler|--quiet|--report-runtime|--s|--server|--status|--update|--verbose|--wiki|--with-maintenance-log]

### rebuildData.php

Recreates all the semantic data in the database

Usage:
- php rebuildData.php
- [--b|--categories|--conf|--d|--dbpass|--dbuser|--debug|--dispose-outdated|--e|--exception-log|--f|--force-update|--globals|--help|--ignore-exceptions|--memory-limit|--n|--no-cache|--p|--page|--profiler|--property-statistics|--query|--quiet|--redirects|--report-poolcache|--report-runtime|--revision-mode|--s|--server|--shallow-update|--skip-properties|--startidfile|--v|--wiki|--with-maintenance-log]

### rebuildElasticIndex.php

Rebuilds the Elasticsearch index.

Usage:
- php rebuildElasticIndex.php
- [--conf|--dbpass|--dbuser|--debug|--delete-all|--e|--force-refresh|--globals|--help|--memory-limit|--page|--profiler|--quiet|--report-runtime|--run-fileindex|--s|--server|--skip-fileindex|--update-settings|--wiki]

### rebuildFulltextSearchTable.php

Rebuilds the fulltext search index.

Usage:
- php rebuildFulltextSearchTable.php
- [--conf|--dbpass|--dbuser|--globals|--help|--memory-limit|--optimize|--profiler|--quick|--quiet|--report-runtime|--server|--v|--wiki|--with-maintenance-log]

### rebuildPropertyStatistics.php

Rebuilds the property usage statistics

Usage:
- php rebuildPropertyStatistics.php
- [--conf|--dbpass|--dbuser|--globals|--help|--memory-limit|--profiler|--quiet|--server|--wiki|--with-maintenance-log]

### removeDuplicateEntities.php

Removes duplicate entities.

Usage:
- php removeDuplicateEntities.php
- [--conf|--dbpass|--dbuser|--globals|--help|--memory-limit|--profiler|--quiet|--s|--server|--wiki]

### setupStore.php

Sets up the storage backend.

Usage:
- php setupStore.php
- [--backend|--conf|--dbpass|--dbuser|--delete|--globals|--help|--memory-limit|--nochecks|--profiler|--quiet|--server|--skip-import|--skip-optimize|--wiki]

### updateEntityCollation.php

Updates the `smw_sort` field.

Usage:
- php updateEntityCollation.php
- [--conf|--dbpass|--dbuser|--globals|--help|--memory-limit|--profiler|--quiet|--s|--server|--wiki]
