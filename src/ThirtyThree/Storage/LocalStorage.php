<?php

namespace ThirtyThree\Storage;

use URL;
use getID3;
use Storage;
use App\Models\UploadFile;
use InvalidArgumentException;

class LocalStorage implements StorageContract
{
    protected $storageInfo;
    protected $storage;

    public function __construct($storage)
    {
        $this->storageInfo = $storage;
        $this->storage = Storage::disk($storage->bucket);
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
        $file = file_get_contents($remotePath);
        $this->storage()->put($path, $file);

        $file = UploadFile::create([
            'driver' => 'local',
            'bucket' => $this->storageInfo->bucket,
            'path' => $path,
            'name' => attachName($remotePath),
            'extension' => attachSuffix($remotePath),
            'status' => 1,
        ]);

        return $file;
    }

    public function baseInfo($path)
    {
        $storageAdapter = $this->storage()->getDriver()->getAdapter();
        $filePath = $storageAdapter->applyPathPrefix($path);
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Local file [{$path}] not exists.");
        }

        return [
            'hash' => hash_file('sha1', $filePath),
            'mime' => mime_content_type($filePath),
            'size' => filesize($filePath),
        ];
    }

    public function videoInfo($path)
    {
        $storageAdapter = $this->storage()->getDriver()->getAdapter();
        $videoPath = $storageAdapter->applyPathPrefix($path);
        if (! file_exists($videoPath)) {
            throw new InvalidArgumentException("Local file [{$path}] not exists.");
        }
        // 获取基本信息
        $getID3 = new getID3();
        $fileAnalyze = $getID3->analyze($videoPath);
        if (! starts_with(array_get($fileAnalyze, 'mime_type'), 'video')) {
            throw new InvalidArgumentException("Local file [{$path}] is not video.");
        }

        return [
            'duration' => array_get($fileAnalyze, 'playtime_seconds'),
            'bit_rate' => array_get($fileAnalyze, 'bitrate'),
            'width' => array_get($fileAnalyze, 'video.resolution_x'),
            'height' => array_get($fileAnalyze, 'video.resolution_y'),
        ];
    }

    public function videoThumbnail(UploadFile $fileInfo)
    {
        $path = $fileInfo->path;
        $storageAdapter = $this->storage()->getDriver()->getAdapter();
        $videoPath = $storageAdapter->applyPathPrefix($path);
        if (! file_exists($videoPath)) {
            throw new InvalidArgumentException("Local file [{$path}] not exists.");
        }

        // todo @timjuly
        throw new \RuntimeException('method not achieve');
        // 生成缩略图
        $ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => '/usr/local/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/local/bin/ffprobe',
        ]);
        $video = $ffmpeg->open($videoPath);
        $thumbnailDir = 'thumbnail/'.date('Y/m/d');
        $thumbnailDirPath = $storageAdapter->applyPathPrefix($thumbnailDir);
        if (! is_dir($thumbnailDirPath)) {
            mkdir($thumbnailDirPath, 0755, true);
        }
        $thumbnailPath = $thumbnailDir.'/'.str_random(40).'.jpg';
        $thubmailSavePath = $storageAdapter->applyPathPrefix($thumbnailPath);
        // 保存图片
        $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(0))
            ->save($thubmailSavePath);

        // 添加记录
        return UploadFile::create([
            'driver' => $this->storageInfo->driver,
            'bucket' => $this->storageInfo->bucket,
            'user_id' => null,
            'title' => null,
            'name' => 'thumbnail.jpg',
            'extension' => 'jpg',
            'path' => $thumbnailPath,
            'size' => filesize($thubmailSavePath),
            'mime' => 'image/jpeg',
            'hash' => hash_file('sha1', $thubmailSavePath),
            'status' => 1,
        ]);
    }

    public function url($path, $expiration = null)
    {
        $visibility = $this->storage()->getDriver()->getConfig()->get('visibility');
        if ($visibility === 'public') {
            $basePath = $this->storage()->url($path);
        } else {
            $expiration = $expiration ?: 3600;
            $basePath = URL::temporarySignedRoute('storage.local.private', $expiration, ['path' => $path]);
        }

        return $basePath;
    }

    public function __call($method, $parameters)
    {
        return $this->storage()->$method(...$parameters);
    }
}
