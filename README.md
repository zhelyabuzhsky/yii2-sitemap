# yii2-sitemap

[![Build Status](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap.svg)](https://travis-ci.org/zhelyabuzhsky/yii2-sitemap)

## About

A Yii2 tool to generate sitemap.xml

## Installation

Add

```
"zhelyabuzhsky/yii2-sitemap": "*"
```

to the require section of your `composer.json` file.


## Usage

Add

```php
'sitemap' =>
  [
    'class' => '\zhelyabuzhsky\sitemap\components\Sitemap',
    'maxUrlsCountInFile' => 10000,
    'sitemapDirectory' => 'frontendmarket/web',
  ],
```

to the components section of your console config file.

Add

```php
public function actionCreateSitemap()
{
  \Yii::$app->sitemap
    ->addModel(Item::class)
    ->addModel(Category::class)
    ->create();
}
```
to you console controller.
