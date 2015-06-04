<?php
namespace zhelyabuzhsky\sitemap\components;

use zhelyabuzhsky\sitemap\models\SitemapEntity;
use yii\base\Component;
use yii\base\Exception;

/**
 * Генератор sitemap
 */
class Sitemap extends Component
{
    /**
     * Максимальное число url в одном файле sitemap
     * @var int
     */
    public $maxUrlsCountInFile;

    /**
     * Директория, в которой будут храниться файлы sitemap
     * @var string
     */
    public $sitemapDirectory;

    /**
     * Директория, в которой будут храниться файлы sitemap
     * @var int
     */
    protected $directory;

    /**
     * Путь до текущего файла sitemap, который генерируется прямо сейчас
     * @var string
     */
    protected $path;

    /**
     * Ресурс текущего файла sitemap, который генерируется прямо сейчас
     * @var resource
     */
    protected $handle;

    /**
     * Количество url в файле sitemap, который генерируется прямо сейчас
     * @var int
     */
    protected $urlCount = 0;

    /**
     * Порядковый номер генерируемого файла sitemap
     * @var int
     */
    protected $filesCount = 0;

    /**
     * Массив источников данных для генерации sitemap
     * @var \yii\db\ActiveQuery[]
     */
    protected $dataSources = [];

    /**
     * Создаёт индексный sitemap.xml.
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
        if (null !==\Yii::$app->urlManager->baseUrl) {
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
     * Обновляем файлы sitemap.
     */
    protected function purgeSitemaps()
    {
        // удаляем старые файлы sitemap
        foreach (glob("{$this->sitemapDirectory}/sitemap*.xml*") as $filePath) {
            unlink($filePath);
        }
        // переименовываем новые файлы (без '_')
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
        fwrite(
            $this->handle,
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
            ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' .
            ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">'
        );
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
            foreach ($dataSource->batch(100) as $entities) {
                foreach ($entities as $entity) {
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
        $this->purgeSitemaps();
    }

    /**
     * Записывает в sitemap сущность.
     *
     * @param SitemapEntity $entity
     */
    protected function writeEntity($entity)
    {
        fwrite(
            $this->handle,
            '<url>' .
            '   <loc>' . $entity->getSitemapLoc() . '</loc>' .
            '   <lastmod>' . $entity->getSitemapLastmod() . '</lastmod>' .
            '   <changefreq>' . $entity->getSitemapChangefreq() . '</changefreq>' .
            '   <priority>' . $entity->getSitemapPriority() . '</priority>' .
            '</url>'
        );
    }
}
