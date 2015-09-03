# PageRenderCache v1.0

"Zero configuration" $page rendering with caching, with simple replacements. Useful if you have complicated templates (or large number of less complicated templates), where you need to do a small number of adjustments based on some sort of condition.

In this case, caching on the template level doesn't completely make sense — because it either uses cache, or doesn't. This module solves this problem by using cache all the time, but allowing us to modify the output by replacement of simple strings.

## Requires
- PHP 5.4+
- ProcessWire 2.6+ [this *probably* isn't needed, but I don't have other version running :)]
- `MarkupCache` module installed

## How to use it

Default call:
```php
$page->renderCache($templateFilename, $templateOptions, $replacements);
```
Where:
- `$templateFilename` is a path (or filename), relative to your site's `templates/` directory, without trailing `.php`
- `$templateOptions` is an array of options we pass through to the `$page->render()` call
- `$replacements` is an array of replacements, in `$key => $replacement` format, where any `{{$key}}` will be replaced with `$replacement` in resulting output.

Additional: to bypass caching, add following to your config
```php
$config->bypassPageRenderCache = true;
```

## Example

Let's say we have a template 'item', called by template 'listings' hundreds of times.

**/site/templates/item.in-listing.php**
```php
<div class="somediv {{additionalClass}}">
  <h1><?= $page->title; ?></h1>
  <?= $page->body; ?>
  This item was bought {{count}} times.
</div>
```

**/site/templates/listing.php**
```php
<div class="listing"><?php
  $items = $page->children;
  foreach($items as $item){
    $item->render('item.in-listing', null, [
      // useful for conditional class which can change over time
      'additionalClass' => 'is-even-'.($items->getItemKey($item)%2),

      // or putting other data (such as $post or $session) to use
      'count' => $someOtherData->count($item->id)
    ]);
  }
?></div>
```

And you're done. Now you can render pages with simple replacement for a cost of single `str_replace`, instead of a complete rebuild.

## Future plans

This is just a version 1, and probably the only one, because I already have my eye on some other things, which will probably require some API changes, thus it will be released as v2. The other things include:
- re-rendering of items on `$page->save()`, for even faster experience for your users
- nested caching
  - requires custom caching methods, not reuse of MarkupCache
  - probably some kind of caching helper in the templates (which will include  
`$page->id` for you, for simplicity, for nested calls)
- cache expiring logic
  - hash of content?
  - hash of parameters?
  - something like `$requires => 'children=1'`, which would automagically expire cache on parent for child changes

## License

See LICENSE.md