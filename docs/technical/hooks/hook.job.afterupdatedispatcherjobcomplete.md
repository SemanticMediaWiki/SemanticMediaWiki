* Since: 2.5
* Description: Hook allows to add extra jobs after `UpdateDispatcherJob` has been processed.
* Reference class: [`UpdateDispatcherJob.php`][UpdateDispatcherJob.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;

\MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Job::AfterUpdateDispatcherJobComplete', function( UpdateDispatcherJob $job ) {

	// Find related dependencies
	$title = $job->getTitle();

	return true;
} );
```

[UpdateDispatcherJob.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Jobs/UpdateDispatcherJob.php
