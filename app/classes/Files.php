<?php

class Files
{
    /**
     * Get used disk size
     * @param array user config (uploadroot, uploadpath, quota, quota_limit)
     * @return array (usedsize, spaceleft, percentused)
     */
    public function diskSize($config)
    {
            $info = array();
            $path = $config["uploadroot"] . $config["uploadpath"];

            if (!is_dir($path) || !file_exists($path))
                throw new \Nette\InvalidArgumentException("Specified path $path does not exists");

            if ($config["quota"] && $config["quota_limit"]) {
                $size = 0;
                foreach (\Nette\Utils\Finder::findFiles("*")->from($path) as $file) {
                                   $size += $this->filesize($file->getPathName());
                }
                $info["usedsize"] = $size;
                $info["spaceleft"] = ($config["quota_limit"] * 1048576) - $size;
                $info["percentused"] = round(($size / ($config["quota_limit"] * 1048576)) * 100);
            } else {
                $freesize = disk_free_space($path);
                $totalsize = disk_total_space($path);
                $info["usedsize"] = $totalsize - $freesize;
                $info["spaceleft"] = $freesize;
                $info["percentused"] = round(($info["usedsize"] / $totalsize ) * 100);
            }

            return $info;
    }

    /**
     * Get file size for files > 2 GB
     * @param string file path
     * @return integer | false
     */
    public function filesize($path)
    {
            if (filesize($path) === 0)
                    return null;

            $filesize = new Ixtrum\FileManager\System\Files\Filesize;

            $return = $filesize->sizeCurl($path);
            if ($return)
                    return $return;

            $return = $filesize->sizeNativeSeek($path);
            if ($return)
                    return $return;

            $return = $filesize->sizeCom($path);
            if ($return)
                    return $return;

            $return = $filesize->sizeExec($path);
            if ($return)
                    return $return;

            $return = $filesize->sizeNativeRead($path);
            if ($return)
                    return $return;

            \Nette\Application\ApplicationException("File size error at file $path.");
    }
}