<?php

namespace zhelyabuzhsky\sitemap\tests;

use zhelyabuzhsky\sitemap\components\Sitemap;
use zhelyabuzhsky\sitemap\tests\models\Category;

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    private $path = 'tests/runtime';

    protected function setUp()
    {
        \Yii::$app->db->createCommand(
            'DROP TABLE IF EXISTS category;'
        )->execute();
        \Yii::$app->db->createCommand(
            'CREATE TABLE category (id INTEGER NOT NULL,
                                    name CHARACTER VARYING(255) NOT NULL,
                                    slug CHARACTER VARYING(255) NOT NULL);'
        )->execute();
        \Yii::$app->db->createCommand(
            'INSERT INTO category VALUES (1, \'Category 1\', \'category_1\'),
                                         (2, \'Category 2\', \'category_2\'),
                                         (3, \'Category 3\', \'category_3\');'
        )->execute();

        file_put_contents($this->path . '/' . 'sitemap.xml', 'test');
    }

    protected function tearDown()
    {
        $sitemapDirectory = $this->path;
        foreach (glob("$sitemapDirectory/sitemap*") as $file) {
            unlink($file);
        }
    }

    public function testInterface()
    {
        $this->setExpectedException('\Exception');
        $mock = $this->getMock('Fake');
        $sitemap = new Sitemap();
        $sitemap->addModel(get_class($mock));
    }

    public function testCreate()
    {
        $sitemap = new Sitemap([
            'maxUrlsCountInFile' => 1
        ]);
        $sitemapDirectory = $sitemap->sitemapDirectory = $this->path;
        $sitemap->addModel(Category::className());
        $sitemap->setDisallowUrls(['#category_2#']);
        $sitemap->create();
        $sitemapFileNames = Array();
        foreach (glob("$sitemapDirectory/sitemap*") as $sitemapFilePath) {
            $sitemapFileNames[] = basename($sitemapFilePath);
        }
        $this->assertEquals($sitemapFileNames, Array
        (
            'sitemap.xml',
            'sitemap.xml.gz',
            'sitemap1.xml',
            'sitemap1.xml.gz',
            'sitemap2.xml',
            'sitemap2.xml.gz',
        ));

        $xmlData = file_get_contents("$sitemapDirectory/sitemap.xml");
        $this->assertNotFalse(strpos($xmlData, '<?xml version="1.0" encoding="UTF-8"?>'));

        $xmlData = file_get_contents("$sitemapDirectory/sitemap2.xml");
        $this->assertNotFalse(strpos($xmlData, '<loc>http://localhost/category_3</loc>'));

        $gzSitemap = gzopen("$sitemapDirectory/sitemap.xml.gz", "r");
        $sitemap = fopen("$sitemapDirectory/sitemap.xml", "r");
        $this->assertEquals(fread($gzSitemap, 2000), fread($sitemap, 2000));
        gzclose($gzSitemap);
        fclose($sitemap);

        $gzSitemap = gzopen("$sitemapDirectory/sitemap2.xml.gz", "r");
        $sitemap = fopen("$sitemapDirectory/sitemap2.xml", "r");
        $this->assertEquals(fread($gzSitemap, 2000), fread($sitemap, 2000));
        gzclose($gzSitemap);
        fclose($sitemap);
    }
}
