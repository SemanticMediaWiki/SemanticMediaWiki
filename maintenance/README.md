# Maintenance scripts

Maintenance scripts are run using the PHP command line.

If you keep Semantic MediaWiki (SMW) in the standard directory `./extensions/SemanticMediaWiki`
(below your MediaWiki installation) then you can run these scripts from almost anywhere.

Otherwise, it is required to set the environment variable `MW_INSTALL_PATH` to the root of your
MediaWiki installation first. This is also required if you use a symbolic link from
`./extensions/SemanticMediaWiki` to the actual installation directory of Semantic MediaWiki.

## Usage

Setting environment variables is different for different operating systems and shells, but can
normally be done from the command line right before the php call. On Bash (Linux), e.g. one can
use the following call to execute "setupStore.php" with a different MediaWiki location:

```
export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php
```
In some setups that use a lot of shared code for many wikis, it might be required to specify the
location of "LocalSettings.php" explicitly, too:

```
export MW_INSTALL_PATH="/path/to/mediawiki" && php setupStore.php --conf=/path/to/mediawiki/LocalSettings.php
```

## Overview

Note:
* This overview only shows the current maintenance scripts and does not include precursing
maintenance scripts with similar functions.
* This overview only shows the script specific parameters. Parameters applicable to all scripts are:  
  * Generic maintenance parameters:  
    [--help|--quiet|--conf|--wiki|--globals|--memory-limit|--server|--profiler|--mwdebug]  
  * Script dependent parameters:  
    [--dbuser|--dbpass|--dbgroupdefaullt]

### disposeOutdatedEntities.php

Allows to dispose outdated entities. Available since SMW 3.2.0

Usage:
- php disposeOutdatedEntities.php

### dumpRDF.php

Allows to do a complete RDF export of existing triples. Available since SMW 2.0.0

Usage:
- php dumpRDF.php
- [--file|--categories|--classes|--concepts|--types|--individuals|--page|--properties|--d|--e]

### populateHashField.php

Allows to populate the "smw_hash" database field for all entities that have a missing entry when
initially upgrading to Semantic MediaWiki 3.0.0 and later. Available since SMW 3.0.1

Usage:
- php populateHashField.php

### purgeEntityCache.php

Allows to purge all cache entries including associates of entities at once. Available since SMW 3.1.0

Usage:
- php purgeEntityCache.php

### rebuildConceptCache.php

Allows to rebuild (create, update, and delete) concept caches. Available since SMW 1.9.2

Usage:
- php rebuildConceptCache.php
- [--status|--create|--delete] [--concept|--hard|--update|--old|-s|-e] [--verbose|--no-cache|--debug|--report-runtime|--with-maintenance-log]

### rebuildData.php

Allows to rebuild all the semantic data for a selected data backend/store. Available since SMW 1.9.2

Usage:
- php rebuildData.php
- [-d|-s|-e|-f|-n|--startidfile|-b|-v|-c|-p|-t|--page|--redirects|--query|-f|--no-cache|--report-runtime|--debug|--skip-properties|--shallow-update|--ignore-exceptions|--exception-log|--with-maintenance-log|--revision-mode|--force-update|--dispose-outdated]

### rebuildElasticIndex.php

Allows to rebuild all the semantic data for the Elasticsearch index. Available since SMW 3.0.0

Usage:
- php rebuildElasticIndex.php
- [--s|--e|--page|--update-settings|--force-refresh|--delete-all|--skip-fileindex|--run-fileindex|--auto-recovery|--only-update|--debug|--report-runtime|--with-maintenance-log]

### rebuildElasticMissingDocuments.php

Allows to find missing entities (aka documents) from the Elasticsearch index and schedule
"smw.update" jobs for those identified subjects. Available since SMW 3.1.0

Usage:
- php rebuildElasticMissingDocuments.php


### rebuildFulltextSearchTable.php

Allows to rebuild the full text search data table of the relational database backend/store.
 Available since SMW 2.5.0

Usage:
- php rebuildFulltextSearchTable.php
- [--v|--quick|--optimize|--report-runtime|--with-maintenance-log]

### rebuildPropertyStatistics.php

Allows to rebuild the property usage statistics. Available since SMW 1.9.0

Usage:
- php rebuildPropertyStatistics.php
- [--with-maintenance-log]

### removeDuplicateEntities.php

Allows to remove all duplicate entities with no reference in any other table from the entity table.
Available since SMW 3.0.0

Usage:
- php removeDuplicateEntities.php
- [--s|--report-runtime|--with-maintenance-log]

### runImport.php

Allows to import content from import files. Available since SMW 3.2.0

Usage:
- php runImport.php

### runLocalMessageCopy.php

Allows to copy local messages from and to the i18n translation system. Available since SMW 3.2.0

Usage:
- php runLocalMessageCopy.php
- [--file|--copy-canonicalmessages|--copy-translatedmessages]

### setupStore.php

Allows to set up the data backend/store. Available since SMW 2.0.0

Usage:
- php setupStore.php
- [--delete|--backend|--nochecks|--skip-optimize|--skip-import] [backend]

### updateEntityCollation.php

Allows to do mass updates of database field "smw_sort" on the occasion that setting for the entity
collation was changed. Available since SMW 3.0.0

Usage:
- php updateEntityCollation.php

### updateEntityCountMap.php

Allows to do mass populating of database field "smw_countmap" when initially upgrading to
Semantic MediaWiki 3.2.0 and later. Available since SMW 3.0.0

Usage:
- php updateEntityCountMap.php

### updateQueryDependencies.php

Allows to update all entities that hold embedded queries. Available since SMW 3.1.0

Usage:
- php updateQueryDependencies.php

