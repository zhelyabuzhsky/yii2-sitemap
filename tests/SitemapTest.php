<?php
namespace zhelyabuzhsky\sitemap\tests;

use zhelyabuzhsky\sitemap\components\Sitemap;

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $sitemap = new Sitemap();
        $sitemapDirectory = $sitemap->sitemapDirectory = 'tests/runtime';
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
        ));
        foreach (glob("$sitemapDirectory/sitemap*") as $file) {
            unlink($file);
        }
    }
}
