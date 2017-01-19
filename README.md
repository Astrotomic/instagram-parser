# Instagram Parser

Parses data from Instagram without API access.

[![StyleCI](https://styleci.io/repos/79472945/shield)](https://styleci.io/repos/79472945)

## Usage

### get Media informations by User

```php
$instagram = new InstagramParser();
$media = $instagram->getUserRecentMedia('user.name');
```
