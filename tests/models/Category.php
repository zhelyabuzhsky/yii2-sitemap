<?php
namespace zhelyabuzhsky\sitemap\tests\models;

use yii\db\ActiveRecord;
use zhelyabuzhsky\sitemap\models\SitemapEntityInterface;

class Category extends ActiveRecord implements SitemapEntityInterface
{

    /**
     * Get lastmod value for sitemap file.
     *
     * @return string
     */
    public function getSitemapLastmod()
    {
        return date('c');
    }

    /**
     * Get changefreq value for sitemap file.
     *
     * @return string
     */
    public function getSitemapChangefreq()
    {
        return 'daily';
    }

    /**
     * Get priority value for sitemap file.
     *
     * @return string
     */
    public function getSitemapPriority()
    {
        return 0.5;
    }

    /**
     * Get loc value for sitemap file.
     *
     * @return string
     */
    public function getSitemapLoc()
    {
        return 'http://localhost/' . $this->slug;
    }

    /**
     * Get data source for sitemap file generation.
     *
     * @return \yii\db\ActiveQuery $dataSource
     */
    public static function getSitemapDataSource()
    {
        return self::find();
    }
}