<?php

namespace ThirtyThree\Storage;

use Qiniu;
use Storage;
use GuzzleHttp\Client;
use App\Models\UploadFile;
use App\Models\FileQiniuFop;

class QiniuStorage implements StorageContract
{
    protected $storageInfo;
    protected $storage;

    public function __construct($storage)
    {
        $this->storageInfo = $storage;
        $this->storage = Qiniu::bucket($storage->bucket);
    }

    public function storage()
    {
        return $this->storage;
    }

    public function info()
    {
        return $this->storageInfo;
    }

    public function fetchFile($path, $remotePath, $options = [])
    {
        return $this->storage()->fetchFile($path, $remotePath);
    }

    public function baseInfo($path)
    {
        $fileInfo = $this->storage()->fileInfo($path);

        return [
            'mime' => $fileInfo['mimeType'],
            'size' => $fileInfo['fsize'],
        ];
    }

    public function videoInfo($path)
    {
        $infoUrl = $this->storage()->url($path.'?avinfo', 3600);
        $client = new Client();
        $response = $client->get($infoUrl);
        $json = json_decode($response->getBody(), true);
        if (empty($json)) {
            throw new \RuntimeException('get qiniu video info error(empty result)');
        }
        $video = null;
        foreach (array_get($json, 'streams', []) as $stream) {
            if (array_get($stream, 'codec_type') == 'video') {
                $video = $stream;
            }
        }
        if (empty($video)) {
            throw new \RuntimeException('get qiniu video info error(wrong result)');
        }

        return [
            'duration' => array_get($video, 'duration'),
            'bit_rate' => array_get($video, 'bit_rate'),
            'width' => array_get($video, 'width'),
            'height' => array_get($video, 'height'),
        ];
    }

    public function videoThumbnail(UploadFile $fileInfo)
    {
        $path = $fileInfo->path;
        $thumbnailDir = 'thumbnail/'.date('Y/md');
        $thumbnailPath = $thumbnailDir.'/'.str_random(40).'.jpg';
        $fops = 'vframe/jpg/offset/0|saveas/'.$this->storage()->saveAs($thumbnailPath);

        $callback = apiRoute('qiniu.fop-callback');
        $fop_id = $this->storage()->PFopExecute($path, $fops, ['notify_url' => $callback]);

        FileQiniuFop::create([
            'fop_id' => $fop_id,
            'bucket_name' => $fileInfo->bucket,
            'key' => $path,
            'usage' => 'video.thumbnail',
            'save_info' => [
                $fops => [
                    'bucket_name' => $fileInfo->bucket,
                    'path' => $thumbnailPath,
                ],
            ],
            'context' => ['file_id' => $fileInfo->id],
        ]);
    }

    public function __call($method, $parameters)
    {
        return $this->storage()->$method(...$parameters);
    }
}
