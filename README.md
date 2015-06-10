# yii2-sitemap

[![Build Status](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap.svg)](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zhelyabuzhsky/yii2-sitemap/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zhelyabuzhsky/yii2-sitemap/?branch=master)
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

## Usage

```php
'sitemap' =>
  [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
    'maxUrlsCountInFile' => 10000,
    'sitemapDirectory' => 'frontend/web',
  ],
```

```php
public function actionCreateSitemap()
{
  \Yii::$app->sitemap
    ->addModel(Item::class)
    ->addModel(Category::class)
    ->create();
}
```

## Testing

``` bash
$ codecept run
```

## Security

If you discover any security related issues, please email zhelyabuzhsky@gmail.com instead of using the issue tracker.

## Credits

- [Ilya Zhelyabuzhsky](https://github.com/zhelyabuzhsky)
- [All Contributors](../../contributors)

## License

GNU General Public License, version 2. Please see [License File](LICENSE) for more information.
