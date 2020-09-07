<?php
/**
 * @package    serverless-image-converter
 * @author     Zoltan Szanto <mrbig00@gmail.com>
 * @copyright  2020 Zoltán Szántó
 */

namespace App;

use Aws\Result;
use Aws\S3\S3Client;
use Imagine\Imagick\Imagine;
use Imagine\Image\Box;
use function GuzzleHttp\Promise\all;

/**
 * Class ImageConverter
 *
 * @package App
 */
class ImageConverter
{
    public $event;
    public $targetBucketName;

    protected $s3;
    protected $baseLocation = "/tmp";
    protected $imagine;

    protected $sizes = [
        '80x80' => ['w' => 200, 'h' => 200],
        '200x200' => ['w' => 200, 'h' => 200],
        '400x400' => ['w' => 400, 'h' => 400],
    ];

    public function __construct($event, $targetBucketName)
    {
        $this->event = $event;
        $this->targetBucketName = $targetBucketName;
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => 'eu-central-1',
        ]);
        $this->imagine = new Imagine();

    }

    public function execute()
    {
        $result = [];
        if (isset($this->event['Records'])) {
            foreach ($this->event['Records'] as $record) {
                if (isset($record['s3']['object'])) {
                    $this->handleFile($record);
                }
            }
        }

        return $result;
    }

    protected function handleFile(array $record): void
    {
        $fileName = basename($record['s3']['object']['key']);
        $path = dirname($record['s3']['object']['key']);
        $this->downloadImage($record['s3']['bucket']['name'], $record['s3']['object']['key']);
        $this->resizeImage($fileName, $path);
    }

    protected function downloadImage($bucket, $file): void
    {
        $fileName = basename($this->sanitizePath($file));
        $file = $this->s3->getObject(['Bucket' => $bucket, 'Key' => $this->sanitizePath($file)]);
        file_put_contents("{$this->baseLocation}/$fileName", $file['Body']->getContents());
    }

    protected function resizeImage($fileName, $path)
    {
        $promises = [];

        foreach ($this->sizes as $size) {
            $newName = "{$size['w']}x{$size['h']}-{$fileName}";
            $tmpLocation = $this->baseLocation . "/" . basename($fileName);
            $this->imagine
                ->open($tmpLocation)
                ->resize(new Box($size['w'], $size['h']))
                ->save("{$this->baseLocation}/{$newName}");

            $key = $this->sanitizePath("{$path}/{$newName}");
            $source = "{$this->baseLocation}/{$newName}";

            $promises []= $this->s3->putObjectAsync([
                'Bucket' => $this->targetBucketName,
                'Key' => $key,
                'SourceFile' => $source,
                "ACL" => 'public-read',

            ]);
        }

        $allPromise = all($promises);
        return $allPromise->wait();
    }

    protected function sanitizePath($input): string
    {
        return preg_replace(
            '/([^:])(\/{2,})/',
            '$1/',
            ltrim($input, './')
        );
    }
}
