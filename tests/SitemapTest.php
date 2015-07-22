<?php
namespace zhelyabuzhsky\sitemap\tests;

use zhelyabuzhsky\sitemap\components\Sitemap;
use zhelyabuzhsky\sitemap\tests\models\Category;

class SitemapTest extends \PHPUnit_Framework_TestCase
{

    private $path = 'tests/runtime';

    public function setUp()
    {
        \Yii::$app->db
            ->createCommand(file_get_contents(__DIR__ . '/db/mysql.sql'))
            ->execute();
        file_put_contents($this->path . '/' . 'sitemap.xml', 'test');
    }

    public function testDoesNotInterface()
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

        foreach (glob("$sitemapDirectory/sitemap*") as $file) {
            unlink($file);
        }
    }
}
