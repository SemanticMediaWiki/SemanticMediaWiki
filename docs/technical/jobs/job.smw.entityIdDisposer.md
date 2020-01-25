## smw.entityIdDisposer

* Description: The job is responsible for removing all [outdated entities][outdated-entities] from the `ID_TABLE` with the help of the [`PropertyTableIdReferenceDisposer.php`][PropertyTableIdReferenceDisposer.php] (removes all remaining references from other tables for a particular ID).
* Reference class: [`EntityIdDisposerJob.php`][EntityIdDisposerJob.php]

## Usage

<pre>
use SMW\Services\ServicesFactory;

$jobFactory = ServicesFactory::getInstance()->newJobFactory();

$entityIdDisposerJob = $jobFactory->newEntityIdDisposerJob(
	$title,
	$parameters
);

$entityIdDisposerJob->insert();
</pre>

## Notes

The job is expected to be executed only when called from the command line (`waitOnCommandLine`) to avoid having the MediaWiki scheduler to dispatch the job while running on GET requests.

[EntityIdDisposerJob.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Jobs/EntityIdDisposerJob.php
[PropertyTableIdReferenceDisposer.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/PropertyTableIdReferenceDisposer.php
[outdated-entities]: https://www.semantic-mediawiki.org/wiki/Help:Outdated_entities
