# Instagram Parser

Parses data from Instagram without API access.

[![StyleCI](https://styleci.io/repos/79472945/shield)](https://styleci.io/repos/79472945)

## Usage

### get Media informations by User

```php
$instagram = new InstagramParser();
$instagram->setConfig('storage_path', '/your/path/for/the/cache/files'); // optional but recommended to change this
$media = $instagram->getUserRecentMedia('user.name');
```
