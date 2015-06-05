<?php
namespace zhelyabuzhsky\sitemap\models;

/**
 * Interface for all entities to write in sitemap.
 */
interface SitemapEntity
{
    /**
     * Get lastmod value for sitemap file.
     *
     * @return string
     */
    public function getSitemapLastmod();

    /**
     * Get changefreq value for sitemap file.
     *
     * @return string
     */
    public function getSitemapChangefreq();

    /**
     * Get priority value for sitemap file.
     *
     * @return string
     */
    public function getSitemapPriority();


    /**
     * Get loc value for sitemap file.
     *
     * @return string
     */
    public function getSitemapLoc();

    /**
     * Get data source for sitemap file generation.
     *
     * @return \yii\db\ActiveQuery $dataSource
     */
    public static function getSitemapDataSource();
}
