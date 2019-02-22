# Sitemap.xml generator for Yii2

[![Build Status](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap.svg)](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap)
[![Total Downloads](https://poser.pugx.org/zhelyabuzhsky/yii2-sitemap/downloads)](https://packagist.org/packages/zhelyabuzhsky/yii2-sitemap)

Yii2 extension to generate sitemap files for large web-sites through Yii2 [console command](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

``` bash
$ php composer.phar require zhelyabuzhsky/yii2-sitemap
```

or add

```
"zhelyabuzhsky/yii2-sitemap": "^1.1"
```

to the require section of your `composer.json` file.

## Features

* Generates multiple sitemaps (large sites)
* Creates index sitemap file
* Gzip compression of .xml files
* Disallow urls support (through regular expression array)

## Configuration

### 1. Configure `urlManager` at console config

```php
'urlManager' => [
    'hostInfo' => 'https://example.com',
    'baseUrl' => '/',
    'rules' => [
      // ...
    ],
],
```

**NOTE** Both params `hostInfo` and `baseUrl` are required for Yii2 console app.

**NOTE** `urlManager` `rules` section usually repeats your frontend `urlManager` configuration, so you could merge it at console config (see https://github.com/yiisoft/yii2/issues/1578#issuecomment-66716648):


<details>
  <summary>Show details</summary>
  
`console/main.php`
```php
$frontendUrlManager = require(__DIR__ . '/../../frontend/config/UrlManager.php');
//...
'urlManager' => array_merge($frontendUrlManager, [
    'hostInfo' => 'https://example.com'
]),
```

`frontend/config/UrlManager.php`

```php
<?php
return [
    'baseUrl' => '/',
    'class' => 'yii\web\UrlManager',
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
      //...
    ],
];

?>
```
</details>

### 2. Configure `Sitemap` component at console config components section

```php
'components' => [
  'sitemap' => [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
  ],
],
```

Example of using extra `Sitemap` params
```php
'components' => [
  'sitemap' => [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
    'maxUrlsCountInFile' => 10000,
    'sitemapDirectory' => 'frontend/web',
    'optionalAttributes' => ['changefreq', 'lastmod', 'priority'],
    'maxFileSize' => '10M',
  ],
 ],
```

where
* `maxUrlsCountInFile` - max count of urls in one sitemap file;
* `sitemapDirectory` - directory to place sitemap files;
* `optionalAttributes` - list of used optional attributes;
* `maxFileSize` - maximal file size. Zero to work without limits. So you can specify the following abbreviations k - kilobytes and m - megabytes. By default 10m.

## Usage

### 1. Impement `SitemapEntityInterface` for the models you want to use at sitemap

<details>
  <summary>Show example</summary>
  
`common\models\Category.php`
```php
use yii\db\ActiveRecord;
use zhelyabuzhsky\sitemap\models\SitemapEntityInterface;

class Category extends ActiveRecord implements SitemapEntityInterface
{
    /**
     * @inheritdoc
     */
    public function getSitemapLastmod()
    {
        return date('c');
    }
    /**
     * @inheritdoc
     */
    public function getSitemapChangefreq()
    {
        return 'daily';
    }
    /**
     * @inheritdoc
     */
    public function getSitemapPriority()
    {
        return 0.5;
    }
    /**
     * @inheritdoc
     */
    public function getSitemapLoc()
    {
        // Use urlManager rules to create urls
        return $url = Yii::$app->urlManager->createAbsoluteUrl([
            'page/view',
            'pageSlug' => $this->slug,
        ]);
        // or directly
        // return 'http://localhost/' . $this->slug;
    }
    /**
     * @inheritdoc
     */
    public static function getSitemapDataSource()
    {
        return self::find();
    }
}
```
</details>

### 2. Create Yii2 [controller for console command](https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console#create-command)

```php
use yii\console\Controller;

class SitemapController extends Controller
{
  public function actionCreate()
  {
    \Yii::$app->sitemap
      ->addModel(Item::className())
      ->addModel(Category::className(), \Yii::$app->db) // Also you can pass \yii\db\Connection to the database connection that you need to use
      ->setDisallowUrls([
        '#url1#',
        '#url2$#',
      ])
      ->create();
    }
}
```

### 3. Run console command

```bash
php yii sitemap/create
```

## Testing

Set enviroment variable SERVER_NAME (e.g. https://example.com)

``` bash
$ ./vendor/bin/phpunit
```

## Security

If you discover any security related issues, please email zhelyabuzhsky@icloud.com instead of using the issue tracker.

## Credits

- [Ilya Zhelyabuzhsky](https://github.com/zhelyabuzhsky)
- [All Contributors](../../contributors)

## License

GNU General Public License, version 3. Please see [License File](LICENSE) for more information.
