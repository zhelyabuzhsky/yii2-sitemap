<?php
namespace tests\unit\components;

use zhelyabuzhsky\sitemap\components\Sitemap;
use Yii;

class ComponentTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertEquals(2, 1 + 1);
        $sitemap = new Sitemap();

        $sitemapDirectory = $sitemap->sitemapDirectory = 'tests/_output';

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
    }
}
