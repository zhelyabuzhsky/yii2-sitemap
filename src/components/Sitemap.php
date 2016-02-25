<?php

namespace zhelyabuzhsky\sitemap\components;

use zhelyabuzhsky\sitemap\models\SitemapEntityInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * Sitemap generator.
 */
class Sitemap extends Component
{
    /**
     * Max count of urls in one sitemap file.
     *
     * @var int
     */
    public $maxUrlsCountInFile;

    /**
     * Directory to place sitemap files.
     *
     * @var string
     */
    public $sitemapDirectory;

    /**
     * Path to current sitemap file.
     *
     * @var string
     */
    protected $path;

    /**
     * Handle of current sitemap file.
     *
     * @var resource
     */
    protected $handle;

    /**
     * Count of urls in current sitemap file.
     *
     * @var int
     */
    protected $urlCount = 0;

    /**
     * Number of current sitemap file.
     *
     * @var int
     */
    protected $filesCount = 0;

    /**
     * Array of data sources for sitemap generation.
     *
     * @var \yii\db\ActiveQuery[]
     */
    protected $dataSources = [];

    /**
     * @var array
     */
    protected $disallowUrls = [];

    /**
     * Create index file sitemap.xml.
     */
    protected function createIndexFile()
    {
        $this->path = "{$this->sitemapDirectory}/_sitemap.xml";
        $this->handle = fopen($this->path, 'w');
        fwrite(
            $this->handle,
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '   <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        );
        $objDateTime = new \DateTime('NOW');
        $lastmod = $objDateTime->format(\DateTime::W3C);

        $baseUrl = 'http://localhost/';
        if (isset(\Yii::$app->urlManager->baseUrl)) {
            $baseUrl = \Yii::$app->urlManager->baseUrl;
        }
        for ($i = 1; $i <= $this->filesCount; $i++) {
            fwrite(
                $this->handle,
                '<sitemap>' .
                "   <loc>{$baseUrl}/sitemap{$i}.xml.gz</loc>" .
                "   <lastmod>{$lastmod}</lastmod>" .
                '</sitemap>'
            );
        }
        fwrite($this->handle, '</sitemapindex>');
        fclose($this->handle);
        $this->gzipFile();
    }

    /**
     * Update sitemap file.
     */
    protected function updateSitemaps()
    {
        // delete old sitemap files
        foreach (glob("{$this->sitemapDirectory}/sitemap*.xml*") as $filePath) {
            unlink($filePath);
        }
        // rename new files (without '_')
        foreach (glob("{$this->sitemapDirectory}/_sitemap*.xml*") as $filePath) {
            $newFilePath = dirname($filePath) . '/' . str_replace('_', '', basename($filePath));
            rename($filePath, $newFilePath);
        }
    }

    /**
     * Write header to sitemap file.
     */
    protected function beginFile()
    {
        $this->filesCount++;
        $this->path = "{$this->sitemapDirectory}/_sitemap{$this->filesCount}.xml";
        $this->handle = fopen($this->path, 'w');
        fwrite(
            $this->handle,
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
            ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' .
            ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'
        );
    }

    /**
     * Write footer to sitemap file.
     */
    protected function closeFile()
    {
        fwrite($this->handle, "\n" . '</urlset>');
        fclose($this->handle);
    }

    /**
     * Gzip sitemap file.
     */
    protected function gzipFile()
    {
        $gzipFileName = $this->path . '.gz';
        $mode = 'wb9';
        $error = false;
        if ($fp_out = gzopen($gzipFileName, $mode)) {
            if ($fp_in = fopen($this->path, 'rb')) {
                while (!feof($fp_in))
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        if ($error)
            return false;
        else
            return $gzipFileName;
    }

    /**
     * Add ActiveQuery from SitemapEntity model to Sitemap model.
     *
     * @param \yii\db\ActiveQuery $dataSource
     */
    public function addDataSource($dataSource)
    {
        $this->dataSources[] = $dataSource;
    }

    /**
     * Add SitemapEntity model to Sitemap model.
     *
     * @param SitemapEntityInterface|string $model
     * @return $this
     * @throws Exception
     */
    public function addModel($model)
    {
        if (!((new $model()) instanceof SitemapEntityInterface)) {
            throw new Exception("Model $model does not implement interface SitemapEntity");
        }
        $this->addDataSource($model::getSitemapDataSource());
        return $this;
    }

    /**
     * Create sitemap file.
     */
    public function create()
    {
        $this->beginFile();

        foreach ($this->dataSources as $dataSource) {
            /** @var \yii\db\ActiveQuery $dataSource */
            foreach ($dataSource->batch(100) as $entities) {
                foreach ($entities as $entity) {
                    if ($this->isDisallowUrl($entity->getSitemapLoc())) {
                        continue;
                    }
                    if ($this->urlCount === $this->maxUrlsCountInFile) {
                        $this->urlCount = 0;
                        $this->closeFile();
                        $this->gzipFile();
                        $this->beginFile();
                    }
                    $this->writeEntity($entity);
                    $this->urlCount++;
                }
            }
        }

        if ($this->urlCount >= 0) {
            $this->closeFile();
            $this->gzipFile();
        }
        $this->createIndexFile();
        $this->updateSitemaps();
    }

    /**
     * Set disallow pattern url.
     *
     * @param array $urls
     * @return $this
     */
    public function setDisallowUrls($urls)
    {
        $this->disallowUrls = $urls;
        return $this;
    }

    /**
     * Checking for validity.
     *
     * @param string $url
     * @return bool
     */
    protected function isDisallowUrl($url)
    {
        foreach ($this->disallowUrls as $disallowUrl) {
            if (preg_match($disallowUrl, $url) != false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Write entity to sitemap file.
     *
     * @param SitemapEntityInterface $entity
     */
    protected function writeEntity($entity)
    {
        fwrite(
            $this->handle,
            "\n" .
            '<url>' . "\n" .
            '   <loc>' . $entity->getSitemapLoc() . '</loc>' . "\n" .
            '   <lastmod>' . $entity->getSitemapLastmod() . '</lastmod>' . "\n" .
            '   <changefreq>' . $entity->getSitemapChangefreq() . '</changefreq>' . "\n" .
            '   <priority>' . $entity->getSitemapPriority() . '</priority>' . "\n" .
            '</url>'
        );
    }
}
