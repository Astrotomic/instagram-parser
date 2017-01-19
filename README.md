# Instagram Parser

Parses data from Instagram without API access.

[![StyleCI](https://styleci.io/repos/79472945/shield)](https://styleci.io/repos/79472945)

## Usage

```php
$instagram = new InstagramParser();
$instagram->setConfig('storage_path', '/your/path/for/the/cache/files'); // optional but recommended

// get recent Media informations by User
$media = $instagram->getUserRecentMedia('username');

// get Media informations by shortcode
$media = $instagram->getShortcodeMedia('shortcode');
```
