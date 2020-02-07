# wp-patrial-cache

`wp-partial-cache`is a Wordpress plugin to help developper to have control over wp cache.

## Usage

On each part you want to cache :

```php
if(has_no_cache('name-of-the-part', 1440))
{
  // code to Cache
  save_cache();
}
```
Only the first parameter is required. The second is the time in minutes. By default it's 120. 0 => never expires. The third parameters is a boolean, by default set to false. If set to true, cache will be user generated. A forth parameters can be used to give a name when a portion is common accross all site (instead of linked to a page).
