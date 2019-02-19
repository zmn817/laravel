<?php

namespace ThirtyThree\Storage;

use App\Models\StorageScene;
use InvalidArgumentException;
use App\Models\Storage as StorageModel;

class StorageManager
{
    protected $app;

    protected $buckets = [];
    protected $disks = [];
    protected $storages;

    public function __construct($app)
    {
        $this->app = $app;
        $this->storages = StorageModel::all();
    }

    public function disk($driver, $bucket)
    {
        $name = $this->disks[$driver.':'.$bucket] ?? $this->getBucketByInfo($driver, $bucket);

        return $this->bucket($name);
    }

    public function bucket($name = null)
    {
        $name = $name ?: $this->getDefaultBucket();

        return $this->buckets[$name] = $this->get($name);
    }

    protected function getBucketByInfo($driver, $bucket)
    {
        $res = $this->storages->where('driver', $driver)
            ->where('bucket', $bucket)
            ->first();

        if (empty($res)) {
            throw new InvalidArgumentException("Storage [{$driver}:{$bucket}] not configured.");
        }

        return $res->name;
    }

    protected function getDefaultBucket()
    {
        $scene = StorageScene::with('storage')->where('platform', 'default')
            ->where('scene', 'default')
            ->first();
        if (! empty($scene)) {
            return $scene->storage->name;
        }

        return config('filesystems.default');
    }

    protected function get($name)
    {
        return $this->buckets[$name] ?? $this->resolve($name);
    }

    protected function resolve($name)
    {
        $storage = StorageModel::where('name', $name)->first();
        if (empty($storage)) {
            throw new InvalidArgumentException("Storage [{$name}] not configured.");
        }
        $driverMethod = 'create'.ucfirst($storage->driver).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($storage);
        }
        throw new InvalidArgumentException("Driver [{$storage->driver}] is not supported.");
    }

    protected function createLocalDriver($storage)
    {
        return new LocalStorage($storage);
    }

    protected function createQiniuDriver($storage)
    {
        return new QiniuStorage($storage);
    }

    public function __call($method, $parameters)
    {
        return $this->bucket()->$method(...$parameters);
    }
}
