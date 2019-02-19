<?php

namespace ThirtyThree\Storage;

use App\Models\UploadFile;

interface StorageContract
{
    public function fetchFile($path, $remotePath, $options = []);

    public function baseInfo($path);

    public function videoInfo($path);

    public function videoThumbnail(UploadFile $file);
}
