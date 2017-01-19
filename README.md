# Instagram Parser

Parses data from Instagram without API access.

[![GitHub release](https://img.shields.io/github/release/Astrotomic/instagram-parser.svg?style=flat-square)](https://github.com/Astrotomic/instagram-parser/releases)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/Astrotomic/instagram-parser/master/LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/Astrotomic/instagram-parser.svg?style=flat-square)](https://github.com/Astrotomic/instagram-parser/issues)

[![StyleCI](https://styleci.io/repos/79472945/shield)](https://styleci.io/repos/79472945)
[![Code Climate](https://img.shields.io/codeclimate/github/Astrotomic/instagram-parser.svg?style=flat-square)](https://codeclimate.com/github/Astrotomic/instagram-parser)

[![Slack Team](https://img.shields.io/badge/slack-astrotomic-orange.svg?style=flat-square)](https://astrotomic.slack.com)
[![Slack join](https://img.shields.io/badge/slack-join-green.svg?style=social)](https://notifynder.signup.team)

## Usage

```php
$instagram = new InstagramParser();
$instagram->setConfig('storage_path', '/your/path/for/the/cache/files'); // optional but recommended

// get recent Media informations by User
$media = $instagram->getUserRecentMedia('username');

// get Media informations by shortcode
$media = $instagram->getShortcodeMedia('shortcode');

// get informations for User
$media = $instagram->getUser('username');

// get recent Media informations by Tag
$media = $instagram->getTagRecentMedia('tag');
```
