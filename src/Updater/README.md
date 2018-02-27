Most DB related updates in Semantic MediaWiki will be manged as [deferred update](https://www.semantic-mediawiki.org/wiki/Deferred_updates)
using the help of MediaWiki's `DeferredUpdates::addUpdate`

- `DeferredCallableUpdate` implements the `DeferrableUpdate` interface in order to be posted via `DeferredUpdates::addUpdate`
  and provides additional functionality for handling specific update requirements in Semantic MediaWiki
- `DeferredTransactionalUpdate` extends `DeferredCallableUpdate` to handle transaction related tasks or isolations
  and hereby ensures an undisturbed update process before and after `MediaWiki::preOutputCommit`.
- `StoreUpdater` is the final instance to manipulate the `SemanticData` before being posted to `Store::updateData`