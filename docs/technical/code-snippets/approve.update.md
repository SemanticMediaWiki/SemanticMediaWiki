For certain user scenarios it may be necessary to refuse or alter an update by changing the revision used as basis for what the semantic data should represent.

Please ensure that when changes are applied to [log][log] those to make them recoverable. See [`$wgLogTypes`][wgLogTypes] on how to add and use an appropriate type and action description.

## Example

The following can be used in connection with the `ApprovedRevs` extension to alter the data representation in Semantic MediaWiki.

### SMW::LinksUpdate::ApprovedUpdate

Check whether the current revision ID used by SMW is the one expected as approved version and if not, refuse its usage by returning `false` to signal that the update should be declined.

```php
use Hooks;
use ApprovedRevs;
use LogPage;
use Xml;

Hooks::register( 'SMW::LinksUpdate::ApprovedUpdate', function( $title, $latestRevID ) {

	if ( !class_exists( 'ApprovedRevs' ) || ( $approvedRevID = ApprovedRevs::getApprovedRevID( $title ) ) === null ) {
		return true;
	}

	if ( $approvedRevID != $latestRevID ) {
		static $logged = [];

		// Only log the action once in case LinksUpdate is called several
		// times by MediaWiki::preOutputCommit/RefreshLinksJob
		if ( isset( $logged[$latestRevID . ':' . $approvedRevID] ) ) {
			return false;
		}

		$log = new LogPage( 'myType' );
		$rev_url = $title->getFullURL( [ 'oldid' => $approvedRevID, 'diff' => $latestRevID ] );

		$rev_link = Xml::element(
			'a',
			[ 'href' => $rev_url ],
			$approvedRevID
		);

		$log->addEntry( 'myType', $title, '', [ $rev_link ] );
		$logged[$latestRevID . ':' . $approvedRevID] = true;

		return false;
	}

	return true;
} );
```

### SMW::Parser::ChangeRevision

During a `UpdateJob` or `rebuildData.php` script execution, SMW always chooses the latest available revision to represent its data.  Use the hook to change the revision when the approved revision is different from what the parser is going to use as basis for the update process.

```php
use Hooks;
use ApprovedRevs;
use LogPage;
use Xml;
use Revision;

Hooks::register( 'SMW::Parser::ChangeRevision', function( $title, &$revision ) {

	if ( !class_exists( 'ApprovedRevs' ) || ( $approvedRevID = ApprovedRevs::getApprovedRevID( $title ) ) === null ) {
		return true;
	}

	// Forcibly change the revision to match the approved version
	$currentRevision = $revision;
	$revision = Revision::newFromId( $approvedRevID );

	$log = new LogPage( 'myType' );
	$rev_url = $title->getFullURL( array( 'oldid' => $approvedRevID ) );

	$rev_link = Xml::element(
		'a',
		[ 'href' => $rev_url ],
		$approvedRevID
	);

	$log->addEntry( 'myType', $title, '', [ $rev_link ] );

	return true;
} );
```

[log]: https://www.mediawiki.org/wiki/Manual:Logging_to_Special:Log
[wgLogTypes]: https://www.mediawiki.org/wiki/Manual:$wgLogTypes
