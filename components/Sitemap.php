<?php
namespace zhelyabuzhsky\sitemap\components;

use zhelyabuzhsky\sitemap\models\SitemapEntity;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Sitemap.
 */
class Sitemap extends Component
{
    public $maxUrlsCountInFile; // максимальное число url в одном файле sitemap
    public $sitemapDirectory; // директория, в которой будут храниться файлы sitemap

    protected $directory; // директория, в которой будут храниться файлы sitemap
    protected $path; // путь до текущего файла sitemap, который генерируется прямо сейчас
    protected $handle; // ресурс текущего файла sitemap, который генерируется прямо сейчас
    protected $urlCount = 0; // количество url в файле sitemap, который генерируется прямо сейчас
    protected $filesCount = 0; // порядковый номер генерируемого файла sitemap
    protected $dataSources = Array(); // список источников данных для генерации sitemap


    /**
     * Создаёт индексный sitemap.xml.
     */
    protected function createIndexFile()
    {
        $this->path = "{$this->sitemapDirectory}/_sitemap.xml";
        $this->handle = fopen($this->path, 'w');
        fwrite($this->handle, '<?xml version="1.0" encoding="UTF-8"?>');
        fwrite($this->handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        $objDateTime = new \DateTime('NOW');
        $lastmod = $objDateTime->format(\DateTime::W3C);
        $baseUrl = \Yii::$app->urlManager->baseUrl;
        for ($i = 1; $i <= $this->filesCount; $i++) {
            fwrite($this->handle, '<sitemap>');
            fwrite($this->handle, "<loc>{$baseUrl}/sitemap{$i}.xml.gz</loc>");
            fwrite($this->handle, "<lastmod>{$lastmod}</lastmod>");
            fwrite($this->handle, '</sitemap>');
        }
        fwrite($this->handle, '</sitemapindex>');
        fclose($this->handle);
        $this->gzipFile();
    }

    /**
     * Обновляем файлы sitemap.
     */
    protected function purgeSitemaps()
    {
        // удаляем старые файлы sitemap
        foreach (glob("{$this->sitemapDirectory}/sitemap*.xml*") as $filePath) {
            unlink($filePath);
        }
        // переименовывем новые файлы без '_'
        foreach (glob("{$this->sitemapDirectory}/_sitemap*.xml*") as $filePath) {
            $newFilePath = dirname($filePath) . '/' . str_replace('_', '', basename($filePath));
            rename($filePath, $newFilePath);
        }
    }

    /**
     * Записывает шапку в файл sitemap.
     */
    protected function beginFile()
    {
        $this->filesCount++;
        $this->path = "{$this->sitemapDirectory}/_sitemap{$this->filesCount}.xml";
        $this->handle = fopen($this->path, 'w');
        fwrite($this->handle, '<?xml version="1.0" encoding="UTF-8"?>');
        fwrite($this->handle,
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">');
    }

    /**
     * Записывает футер в файл sitemap.
     */
    protected function closeFile()
    {
        fwrite($this->handle, '</urlset>');
        fclose($this->handle);
    }

    /**
     * Архивирует файл sitemap.
     */
    protected function gzipFile()
    {
        $gzipFileName = $this->path . '.gz';
        // нужно делать именно так gzip файл, чтобы не было ошибок "unable fork process"
        exec("cat {$this->path} | gzip > $gzipFileName");
    }

    /**
     * @param \yii\db\ActiveQuery $dataSource
     */
    public function addDataSource($dataSource)
    {
        $this->dataSources[] = $dataSource;
    }

    /**
     * @param SitemapEntity $model
     * @return $this
     * @throws Exception
     */
    public function addModel($model)
    {
        if (!((new $model()) instanceof SitemapEntity)) {
            throw new Exception("Model $model does not implement interface SitemapEntity");
        }
        $this->addDataSource($model::getSitemapDataSource());
        return $this;
    }

    /**
     * Создаёт файл sitemap.
     */
    public function create()
    {
        $this->beginFile();

        foreach ($this->dataSources as $dataSource) {
            /** @var \yii\db\ActiveQuery $dataSource */
            foreach ($dataSource->each() as $entity) {
                if ($this->urlCount == $this->maxUrlsCountInFile) {
                    $this->urlCount = 0;
                    $this->closeFile();
                    $this->gzipFile();
                    $this->beginFile();
                }
                $this->writeEntity($entity);
                $this->urlCount++;
            }
        }

        if ($this->urlCount > 0) {
            $this->closeFile();
            $this->gzipFile();
        }
        $this->createIndexFile();
        $this->purgeSitemaps();
    }

    /**
     * Записывает в sitemap сущность.
     *
     * @param SitemapEntity $entity
     */
    protected function writeEntity($entity)
    {
        fwrite($this->handle, '<url>');
        fwrite($this->handle, '<loc>' . $entity->getSitemapLoc() . '</loc>');
        fwrite($this->handle, '<lastmod>' . $entity->getSitemapLastmod() . '</lastmod>');
        fwrite($this->handle, '<changefreq>' . $entity->getSitemapChangefreq() . '</changefreq>');
        fwrite($this->handle, '<priority>' . $entity->getSitemapPriority() . '</priority>');
        fwrite($this->handle, '</url>');
    }
}
