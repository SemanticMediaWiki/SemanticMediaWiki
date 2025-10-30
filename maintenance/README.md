# Maintenance scripts

Maintenance scripts are run using the PHP command line.

If you keep Semantic MediaWiki (SMW) in the standard directory `./extensions/SemanticMediaWiki`
(below your MediaWiki installation) then you can run these scripts from almost anywhere.

Otherwise, it is required to set the environment variable `MW_INSTALL_PATH` to the root of your
MediaWiki installation first. This is also required if you use a symbolic link from
`./extensions/SemanticMediaWiki` to the actual installation directory of Semantic MediaWiki.

## Table of Contents

- [Usage](#usage)
- [Overview](#overview)
- [Maintenance Scripts](#maintenance-scripts)
  - [disposeOutdatedEntities.php](#disposeoutdatedentitiesphp)
  - [dumpRDF.php](#dumprdfphp)
  - [populateHashField.php](#populatehashfieldphp)
  - [purgeEntityCache.php](#purgeentitycachephp)
  - [rebuildConceptCache.php](#rebuildconceptcachephp)
  - [rebuildData.php](#rebuilddataphp)
  - [rebuildElasticIndex.php](#rebuildelasticindexphp)
  - [rebuildElasticMissingDocuments.php](#rebuildelasticmissingdocumentsphp)
  - [rebuildFulltextSearchTable.php](#rebuildfulltextsearchtablephp)
  - [rebuildPropertyStatistics.php](#rebuildpropertystatisticsphp)
  - [removeDuplicateEntities.php](#removeduplicateentitiesphp)
  - [runImport.php](#runimportphp)
  - [runLocalMessageCopy.php](#runlocalmessagecopyphp)
  - [setupStore.php](#setupstorephp)
  - [updateEntityCollation.php](#updateentitycollationphp)
  - [updateEntityCountMap.php](#updateentitycountmapphp)
  - [updateQueryDependencies.php](#updatequerydependenciesphp)

## Usage

Setting environment variables is different for different operating systems and shells, but can
normally be done from the command line right before the php call. On Bash (Linux), e.g. one can
use the following call to execute "setupStore.php" with a different MediaWiki location:

```sh
export MW_INSTALL_PATH="/path/to/mediawiki" && php maintenance/run.php SemanticMediaWiki:setupStore
```
In some setups that use a lot of shared code for many wikis, it might be required to specify the
location of "LocalSettings.php" explicitly, too:

```sh
export MW_INSTALL_PATH="/path/to/mediawiki" && php maintenance/run.php SemanticMediaWiki:setupStore --conf=/path/to/mediawiki/LocalSettings.php
```

## Overview

Note:
* This overview only shows the current maintenance scripts and does not include preceding
maintenance scripts with similar functions.
* This overview only shows the script specific parameters. Parameters applicable to all scripts are:  
  * Generic maintenance parameters:  
    [--help|--quiet|--conf|--wiki|--globals|--memory-limit|--server|--profiler|--mwdebug]  
  * Script dependent parameters:
    [--dbuser|--dbpass|--dbgroupdefault]

## Maintenance Scripts

### disposeOutdatedEntities.php

Allows to dispose outdated entities. Available since SMW 3.2.0

See also: [Help:Maintenance_script_disposeOutdatedEntities.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_disposeOutdatedEntities.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:disposeOutdatedEntities
```

### dumpRDF.php

Allows to do a complete RDF export of existing triples. Available since SMW 2.0.0

See also: [Help:Maintenance_script_dumpRDF.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_dumpRDF.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:dumpRDF [--file|--categories|--classes|--concepts|--types|--individuals|--page|--properties|--d|--e]
```

### populateHashField.php

Allows to populate the "smw_hash" database field for all entities that have a missing entry when
initially upgrading to Semantic MediaWiki 3.0.0 and later. Available since SMW 3.0.1

See also: [Help:Maintenance_script_populateHashField.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_populateHashField.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:populateHashField
```

### purgeEntityCache.php

Allows to purge all cache entries including associates of entities at once. Available since SMW 3.1.0

See also: [Help:Maintenance_script_purgeEntityCache.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_purgeEntityCache.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:purgeEntityCache
```

### rebuildConceptCache.php

Allows to rebuild (create, update, and delete) concept caches. Available since SMW 1.9.2

See also: [Help:Maintenance_script_rebuildConceptCache.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildConceptCache.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildConceptCache [--status|--create|--delete] [--concept|--hard|--update|--old|-s|-e] [--verbose|--no-cache|--debug|--report-runtime|--with-maintenance-log]
```

### rebuildData.php

Allows to rebuild all the semantic data for a selected data backend/store. Available since SMW 1.9.2

See also: [Help:Maintenance_script_rebuildData.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildData.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildData [-d|-s|-e|-f|-n|--startidfile|-b|-v|-c|-p|-t|--page|--redirects|--query|-f|--no-cache|--report-runtime|--debug|--skip-properties|--shallow-update|--ignore-exceptions|--exception-log|--with-maintenance-log|--revision-mode|--force-update|--dispose-outdated]
```

### rebuildElasticIndex.php

Allows to rebuild all the semantic data for the Elasticsearch index. Available since SMW 3.0.0

See also: [Help:Maintenance_script_rebuildElasticIndex.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildElasticIndex.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildElasticIndex [--s|--e|--page|--update-settings|--force-refresh|--delete-all|--skip-fileindex|--run-fileindex|--auto-recovery|--only-update|--debug|--report-runtime|--with-maintenance-log]
```

### rebuildElasticMissingDocuments.php

Allows to find missing entities (aka documents) from the Elasticsearch index and schedule
"smw.update" jobs for those identified subjects. Available since SMW 3.1.0

See also: [Help:Maintenance_script_RebuildElasticMissingDocuments.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_RebuildElasticMissingDocuments.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildElasticMissingDocuments
```

### rebuildFulltextSearchTable.php

Allows to rebuild the full text search data table of the relational database backend/store.
 Available since SMW 2.5.0

See also: [Help:Maintenance_script_rebuildFulltextSearchTable.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildFulltextSearchTable.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildFulltextSearchTable [--v|--quick|--optimize|--report-runtime|--with-maintenance-log]
```

### rebuildPropertyStatistics.php

Allows to rebuild the property usage statistics. Available since SMW 1.9.0

See also: [Help:Maintenance_script_rebuildPropertyStatistics.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_rebuildPropertyStatistics.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:rebuildPropertyStatistics [--with-maintenance-log]
```

### removeDuplicateEntities.php

Allows to remove all duplicate entities with no reference in any other table from the entity table.
Available since SMW 3.0.0

See also: [Help:Maintenance_script_removeDuplicateEntities.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_removeDuplicateEntities.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:removeDuplicateEntities [--s|--report-runtime|--with-maintenance-log]
```

### runImport.php

Allows to import content from import files. Available since SMW 3.2.0

See also: [Help:Maintenance_script_runImport.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_runImport.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:runImport
```

### runLocalMessageCopy.php

Allows to copy local messages from and to the i18n translation system. Available since SMW 3.2.0

See also: [Help:Maintenance_script_runLocalMessageCopy.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_runLocalMessageCopy.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:runLocalMessageCopy [--file|--copy-canonicalmessages|--copy-translatedmessages]
```

### setupStore.php

Allows to set up the data backend/store. Available since SMW 2.0.0

See also: [Help:Maintenance_script_setupStore.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_setupStore.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:setupStore [--delete|--backend|--nochecks|--skip-optimize|--skip-import] [backend]
```

### updateEntityCollation.php

Allows to do mass updates of database field "smw_sort" on the occasion that setting for the entity
collation was changed. Available since SMW 3.0.0

See also: [Help:Maintenance_script_updateEntityCollation.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_updateEntityCollation.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:updateEntityCollation
```

### updateEntityCountMap.php

Allows to do mass populating of database field "smw_countmap" when initially upgrading to
Semantic MediaWiki 3.2.0 and later. Available since SMW 3.0.0

See also: [Help:Maintenance_script_updateEntityCountMap.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_updateEntityCountMap.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:updateEntityCountMap
```

### updateQueryDependencies.php

Allows to update all entities that hold embedded queries. Available since SMW 3.1.0

See also: [Help:Maintenance_script_updateQueryDependencies.php](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_updateQueryDependencies.php)

Usage:
```sh
php maintenance/run.php SemanticMediaWiki:updateQueryDependencies
```

