<?php

namespace ThirtyThree\Qiniu;

use Qiniu\Auth;
use Qiniu\Config;
use RuntimeException;
use App\Models\UploadFile;
use App\Models\FileQiniuFop;
use InvalidArgumentException;
use App\Models\FileQiniuBucket;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Processing\PersistentFop;
use GuzzleHttp\Client as GuzzleClient;

class Qiniu
{
    protected $bucket;
    protected $auth;
    protected $config;
    protected $uploadManager;
    protected $bucketManager;

    public function __construct($slug)
    {
        $bucket = FileQiniuBucket::where('slug', $slug)->first();
        if (empty($bucket)) {
            throw new InvalidArgumentException("Qiniu bucket [{$slug}] not configured.");
        }
        $this->bucket = $bucket;
    }

    /**
     * 七牛 Auth.
     *
     * @return \Qiniu\Auth
     */
    protected function auth()
    {
        if (is_null($this->auth)) {
            $this->auth = new Auth($this->bucket->access_key, $this->bucket->secret_key);
        }

        return $this->auth;
    }

    /**
     * 七牛 Config.
     *
     * @return \Qiniu\Config
     */
    protected function config()
    {
        if (is_null($this->config)) {
            $this->config = new Config();
        }

        return $this->config;
    }

    public function uploadManager()
    {
        if (is_null($this->uploadManager)) {
            $this->uploadManager = new UploadManager($this->config());
        }

        return $this->uploadManager;
    }

    public function bucketManager()
    {
        if (is_null($this->bucketManager)) {
            $this->bucketManager = new BucketManager($this->auth(), $this->config());
        }

        return $this->bucketManager;
    }

    /**
     * 另存为指令生成.
     *
     * @param $key (保存的路径)
     *
     * @return string
     */
    public function saveAs($key)
    {
        return \Qiniu::uriEncode($this->bucket->bucket.':'.$key);
    }

    /**
     * Fop 指令.
     *
     * @param       $key      (保存的路径)
     * @param       $fops     (指令)
     * @param array $optional (额外配置)
     *
     * @return string ID
     *
     * @throws RuntimeException()
     */
    public function PFopExecute($key, $fops, array $optional = [])
    {
        $pFopManager = new PersistentFop($this->auth(), $this->config());

        $pipeline = array_get($optional, 'pipeline');
        $notify_url = array_get($optional, 'notify_url');
        $force = array_get($optional, 'force', false);

        list($id, $error) = $pFopManager->execute($this->bucket->bucket, $key, $fops, $pipeline, $notify_url, $force);

        if (! empty($error)) {
            throw new RuntimeException('转码失败:'.$error->message());
        }

        return $id;
    }

    /**
     * 获取上传 Token.
     *
     * @param       $key      (文件存放路径)
     * @param array $optional (额外配置)
     *
     * @return array
     */
    public function token($key, array $optional = [])
    {
        $optional['returnBody'] = json_encode([
            'bucket' => '$(bucket)',
            'key' => '$(key)',
            'etag' => '$(etag)',
            'fname' => '$(fname)',
            'fsize' => '$(fsize)',
            'mimeType' => '$(mimeType)',
            'endUser' => '$(endUser)',
            'persistentId' => '$(persistentId)',
            'ext' => '$(ext)',
            'uuid' => '$(uuid)',
        ]);

        $token = $this->auth()->uploadToken($this->bucket->bucket, $key, 86400, $optional, false);

        return [$this->bucket, $token];
    }

    /**
     * 上传本地文件.
     *
     * @param       $key      (保存的路径)
     * @param       $filePath (本地文件路径)
     * @param array $optional (额外配置)
     *
     * @return mixed
     *
     * @throws RuntimeException()
     */
    public function putFile($key, $filePath, array $optional = [])
    {
        list($_, $token) = $this->token($key, $optional);
        $uploadManager = $this->uploadManager();

        if (! file_exists($filePath)) {
            throw new RuntimeException("File [$filePath] not exists.");
        }
        $mime = mime_content_type($filePath);
        $hash = hash_file('sha1', $filePath);

        list($result, $error) = $uploadManager->putFile($token, $key, $filePath,
            empty($optional) ? null : $optional, $mime);

        if (! empty($error)) {
            throw new RuntimeException('上传失败:'.$error->message());
        }

        $file = UploadFile::create([
            'driver' => 'qiniu',
            'bucket' => $this->bucket->slug,
            'path' => $key,
            'name' => attachName($filePath),
            'extension' => attachSuffix($filePath),
            'size' => $result['fsize'],
            'mime' => $mime,
            'hash' => $hash,
            'status' => 1,
        ]);

        return $file;
    }

    /**
     * 上传远程文件.
     *
     * @param $key    (保存的路径)
     * @param $remote (远程文件地址)
     *
     * @return App\Models\UploadFile
     *
     * @throws RuntimeException()
     */
    public function fetchFile($key, $remote)
    {
        $bucketManager = $this->bucketManager();
        list($result, $error) = $bucketManager->fetch($remote, $this->bucket->bucket, $key);

        if (! empty($error)) {
            throw new RuntimeException('抓取失败:'.$error->message());
        }

        $file = UploadFile::create([
            'driver' => 'qiniu',
            'bucket' => $this->bucket->slug,
            'path' => $key,
            'name' => attachName($remote),
            'extension' => attachSuffix($remote),
            'size' => $result['fsize'],
            'mime' => $result['mimeType'],
            'status' => 1,
        ]);

        return $file;
    }

    /**
     * 上传远程文件.
     *
     * @param $key    (保存的路径)
     * @param $remote (远程文件地址)
     *
     * @return App\Models\UploadFile
     *
     * @throws RuntimeException()
     */
    public function fetchFileAsync($key, $remote)
    {
        $callback = apiRoute('qiniu.upload-callback');

        $body = [
            'bucket' => $this->bucket->bucket,
            'key' => $key,
            'url' => $remote,
            'callbackurl' => $callback,
            'callbackbody' => json_encode([
                'bucket_name' => $this->bucket->slug,
                'bucket' => '$(bucket)',
                'key' => '$(key)',
                'etag' => '$(etag)',
                'fname' => '$(fname)',
                'fsize' => '$(fsize)',
                'mimeType' => '$(mimeType)',
                'endUser' => '$(endUser)',
                'persistentId' => '$(persistentId)',
                'ext' => '$(ext)',
                'uuid' => '$(uuid)',
            ]),
            'callbackbodytype' => 'application/json',
        ];

        $client = new Client($this->bucket, $this->auth());

        try {
            $client->fetchAsync($body);
        } catch (\Exception $e) {
            throw new RuntimeException('异步抓取失败:'.$e->getMessage());
            report($e);
        }

        $file = UploadFile::create([
            'driver' => 'qiniu',
            'bucket' => $this->bucket->slug,
            'path' => $key,
            'name' => attachName($remote),
            'extension' => attachSuffix($remote),
            'status' => 2,
        ]);

        return $file;
    }

    /**
     * 文件地址
     *
     * @param $path       (路径)
     * @param $expiration (有效期)
     *
     * @return string
     */
    public function url($path, $expiration = null)
    {
        $bucketInfo = $this->bucket;

        $url = $bucketInfo->domain.'/'.ltrim($path, '/');
        if ($bucketInfo->visibility === 'public') {
            return $url;
        }
        $expiration = $expiration ?: 3600;

        return $this->auth()->privateDownloadUrl($url, $expiration);
    }

    /**
     * 文件信息.
     *
     * @param $key
     *
     * @return array
     *
     * @throws RuntimeException()
     */
    public function fileInfo($key)
    {
        $bucketManager = $this->bucketManager();
        list($result, $error) = $bucketManager->stat($this->bucket->bucket, $key);

        if (! empty($error)) {
            throw new RuntimeException('获取七牛文件信息失败:'.$error->message());
        }

        return $result;
    }

    /**
     * 文件 Hash.
     *
     * @param        $key
     * @param string $algo
     *
     * @return string
     *
     * @throws RuntimeException()
     */
    public function fileHash($key, $algo = 'sha1')
    {
        $hashUrl = $this->url($key.'?qhash/'.$algo, 3600);

        try {
            $hash = json_decode(with(new GuzzleClient())->request('GET', $hashUrl)->getBody(), true);
            $hash = $hash['hash'];
        } catch (\Exception $e) {
            throw new RuntimeException('获取七牛文件 Hash 值失败:'.$e->getMessage());
        }

        return $hash;
    }

    /**
     * 异步获取文件 Hash.
     *
     * @param        $key
     * @param string $algo
     */
    public function fileHashAsync($key, $algo = 'sha1')
    {
        $fops = 'qhash/'.$algo;
        $resultPath = 'command/qhash/'.date('Y/md').'/'.str_random(40).'.json';
        $fops .= '|saveas/'.$this->saveAs($resultPath);

        $callback = apiRoute('qiniu.fop-callback');

        $fop_id = $this->PFopExecute($key, $fops, ['notify_url' => $callback]);

        FileQiniuFop::create([
            'fop_id' => $fop_id,
            'bucket_name' => $this->bucket->slug,
            'key' => $key,
            'usage' => 'file.hash',
            'context' => [
                'result' => $resultPath,
            ],
        ]);
    }

    /**
     * 空间域名列表.
     *
     * @return array
     *
     * @throws RuntimeException()
     */
    public function domains()
    {
        $bucketManager = $this->bucketManager();
        list($result, $error) = $bucketManager->domains($this->bucket->bucket);

        if (! empty($error)) {
            throw new RuntimeException('获取七牛空间域名失败:'.$error->message());
        }

        return $result;
    }
}
