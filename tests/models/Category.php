<?php

namespace zhelyabuzhsky\sitemap\tests\models;

use yii\db\ActiveRecord;
use zhelyabuzhsky\sitemap\models\SitemapEntityInterface;

class Category extends ActiveRecord implements SitemapEntityInterface
{
    /**
     * @inheritdoc
     */
    public function getSitemapLastmod()
    {
        return date('c');
    }

    /**
     * @inheritdoc
     */
    public function getSitemapChangefreq()
    {
        return 'daily';
    }

    /**
     * @inheritdoc
     */
    public function getSitemapPriority()
    {
        return 0.5;
    }

    /**
     * @inheritdoc
     */
    public function getSitemapLoc()
    {
        return 'http://localhost/' . $this->slug;
    }

    /**
     * @inheritdoc
     */
    public static function getSitemapDataSource()
    {
        return self::find();
    }
}
