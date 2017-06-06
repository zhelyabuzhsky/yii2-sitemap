# yii2-sitemap

[![Build Status](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap.svg)](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap)
[![Total Downloads](https://poser.pugx.org/zhelyabuzhsky/yii2-sitemap/downloads)](https://packagist.org/packages/zhelyabuzhsky/yii2-sitemap)

A Yii2 extension to generate sitemap files for large web-sites in console

## Install

Via Composer

``` bash
$ composer require zhelyabuzhsky/yii2-sitemap
```

## Features

* multiple sitemaps (large sites)
* index sitemap
* gzip
* disallow urls (regular expression array)

## Usage

### Basic initialization

```php
'sitemap' =>
  [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
  ],
```

### Advanced initialization

```php
'sitemap' =>
  [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
    'maxUrlsCountInFile' => 10000,
    'sitemapDirectory' => '@frontend/web',
    'urlManager' => 'urlManagerFrontend'
    'optionalAttributes' => ['changefreq', 'lastmod', 'priority'],
    'maxFileSize' => '10M',
  ],
```

where
* maxUrlsCountInFile - max count of urls in one sitemap file;
* sitemapDirectory - directory to place sitemap files;
* optionalAttributes - list of used optional attributes;
* maxFileSize - maximal file size. Zero to work without limits. So you can specify the following abbreviations k - kilobytes and m - megabytes. By default 10m.

### Console action

```php
public function actionCreateSitemap()
{
  \Yii::$app->sitemap
    ->addModel(Item::className())
    ->addModel(Category::className())
    ->setDisallowUrls([
      '#url1#',
      '#url2$#',
    ])
    ->create();
}
```

or 

```php
public function actionCreateSitemap()
{
  \Yii::$app->sitemap
    ->sitemapDirectory('@frontend/web')
    ->urlManager('urlManagerFrontend')
    ->addModel(Item::className())
    ->addModel(Category::className())
    ->setDisallowUrls([
      '#url1#',
      '#url2$#',
    ])
    ->create();
}
```

## Testing

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
