<?php
namespace consolemarket\modules\sitemap\models;

/**
 * Интерфейс для всех сущностей, которые могут записываться в sitemap.
 */
interface SitemapEntity
{
    /**
     * Возвращает строку для значения lastmod файла sitemap.
     *
     * @return string
     */
    public function getSitemapLastmod();

    /**
     * Возвращает строку для значения changefreq файла sitemap.
     *
     * @return string
     */
    public function getSitemapChangefreq();

    /**
     * Возвращает строку для значения priority файла sitemap.
     *
     * @return string
     */
    public function getSitemapPriority();


    /**
     * Возвращает строку для значения loc файла sitemap.
     *
     * @return string
     */
    public function getSitemapLoc();

    /**
     * Возвращает источник данных для генерации файла sitemap.
     *
     * @return \yii\db\ActiveQuery $dataSource
     */
    public static function getSitemapDataSource();
}
