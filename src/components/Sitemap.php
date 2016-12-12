<?php

namespace zhelyabuzhsky\sitemap\components;

use yii\base\InvalidConfigException;
use zhelyabuzhsky\sitemap\models\SitemapEntityInterface;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\web\UrlManager;

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
    public $sitemapDirectory = '@app/web';

    /**
     * Url Manager
     *
     * @var string|UrlManager
     */
    public $urlManager = 'urlManager';

    /**
     * List of used optional attributes.
     *
     * @var string[]
     */
    public $optionalAttributes = ['changefreq', 'lastmod', 'priority'];

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
     * Maximal size of sitemap files.
     * Default value: 10M
     *
     * @var int
     */
    protected $maxFileSize = 10485760; // 10 * 1024 * 1024

    /**
     * Generated sitemap groups file count.
     *
     * @var int
     */
    protected $fileIndex = 0;

    /**
     * List of generated files.
     *
     * @var string[]
     */
    protected $generatedFiles = [];

    /**
     * Create index file sitemap.xml.
     */
    protected function createIndexFile()
    {
        if (empty($this->urlManager)) {
            throw new InvalidConfigException("Sitemap::urlManager is invalid.");
        }

        $this->path = "{$this->sitemapDirectory}/_sitemap.xml";
        $this->handle = fopen($this->path, 'w');
        fwrite(
            $this->handle,
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        );
        $objDateTime = new \DateTime('NOW');
        $lastmod = $objDateTime->format(\DateTime::W3C);

        $baseUrl = 'http://localhost/';
        if (isset($this->urlManager->baseUrl)) {
            $baseUrl = $this->urlManager->baseUrl;
        }
        foreach ($this->generatedFiles as $fileName) {
            fwrite(
                $this->handle,
                PHP_EOL .
                '<sitemap>' . PHP_EOL .
                "\t" . '<loc>' . $baseUrl . '/' . $fileName . '.gz' . '</loc>' . PHP_EOL .
                "\t" . '<lastmod>' . $lastmod . '</lastmod>' . PHP_EOL .
                '</sitemap>'
            );
        }
        fwrite($this->handle, PHP_EOL . '</sitemapindex>');
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
            $newFilePath = dirname($filePath) . '/' . substr(basename($filePath), 1);
            rename($filePath, $newFilePath);
        }
    }

    /**
     * Write header to sitemap file.
     */
    protected function beginFile()
    {
        ++$this->fileIndex;
        $this->urlCount = 0;

        $fileName = 'sitemap' . $this->fileIndex . '.xml';
        $this->path = $this->sitemapDirectory . '/_' . $fileName;
        $this->generatedFiles[] = $fileName;

        $this->handle = fopen($this->path, 'w');
        fwrite(
            $this->handle,
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL .
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
        fwrite($this->handle, PHP_EOL . '</urlset>');
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

    public function init()
    {
        parent::init();
        if (!empty($this->sitemapDirector)) {
            $this->sitemapDirectory = Yii::getAlias($this->sitemapDirectory);
            if (!is_writable($this->sitemapDirectory)) {
                throw new InvalidConfigException("Sitemap::sitemapDirectory is not writable.");
            }
        }
        if (!empty($this->urlManager)) {
            if (is_string($this->urlManager)) {
                $this->urlManager = Yii::$app->get($this->urlManager);
            }
            if ($this->urlManager instanceof UrlManager) {
                throw new InvalidConfigException("Sitemap::urlManager is invalid.");
            }
        }
    }

    public function sitemapDirectory($directory)
    {
        $directory = Yii::getAlias($directory);
        if (is_writable($directory)) {
            $this->sitemapDirectory = $directory;
        }
    }

    public function urlManager($urlManager)
    {
        if (is_string($urlManager)) {
            $urlManager = Yii::$app->get($urlManager);
        }
        if ($urlManager instanceof UrlManager) {
            $this->urlManager = $urlManager;
        }
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
        $this->fileIndex = 0;
        $this->beginFile();

        foreach ($this->dataSources as $dataSource) {
            /** @var \yii\db\ActiveQuery $dataSource */
            foreach ($dataSource->batch(100) as $entities) {
                foreach ($entities as $entity) {
                    if (!$this->isDisallowUrl($entity->getSitemapLoc())) {
                        $this->writeEntity($entity);
                    }
                }
            }
        }

        if (is_resource($this->handle)) {
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
     * Set maximal size of sitemap files
     *
     * @param int|string    $size   Maximal file size. Zero to work without limits.
     * So you can specify the following abbreviations k - kilobytes and m - megabytes.
     */
    public function setMaxFileSize($size)
    {
        $fileSizeAbbr = ['k', 'm'];
        if (!is_int($size)) {
            if (is_string($size) && preg_match('/^([\d]*)(' . implode('|', $fileSizeAbbr) . ')?$/i', $size, $matches)) {
                $size = $matches[1];
                if (isset($matches[2])) {
                    $size = $size * pow(1024, array_search(strtolower($matches[2]), $fileSizeAbbr) + 1);
                }
            } else {
                $size = intval($size);
            }
        }
        $this->maxFileSize = $size;
    }

    /**
     * Method checks limits for write in the current file.
     *
     * @param int   $strLen Size of writable string
     * @return boolean
     */
    public function isLimitExceeded($strLen)
    {
        $isStrLenExceeded = function ($strLen) {
            $fileStat = fstat($this->handle);
            return $fileStat['size'] + $strLen > $this->maxFileSize;
        };

        return
            ($this->maxUrlsCountInFile > 0 && $this->urlCount === $this->maxUrlsCountInFile) ||
            ($this->maxFileSize > 0 && $isStrLenExceeded($strLen));
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
        $str = PHP_EOL . '<url>' . PHP_EOL;

        foreach (
            array_merge(
                ['loc'],
                $this->optionalAttributes
            ) as $attribute
        ) {
            $str .= sprintf("\t<%s>%s</%1\$s>", $attribute, call_user_func([$entity, 'getSitemap' . $attribute])) . PHP_EOL;
        }

        $str .= '</url>';

        if ($this->isLimitExceeded(strlen($str))) {
            $this->closeFile();
            $this->gzipFile();
            $this->beginFile();
        }

        fwrite($this->handle, $str);
        ++$this->urlCount;
    }
}
