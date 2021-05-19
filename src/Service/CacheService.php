<?php 
namespace App\Service;

class CacheService
{
    public function get($minutes, $pageId = 0)
    {
        $url = strtok($_SERVER["REQUEST_URI"], '?');
        
        $page = ($pageId > 0) ? '_' + $pageId : '';

        $cacheFile = 'Cache' . $url . $page . '.json';

        if (!file_exists($cacheFile)) {
            return '';
        }

        $cacheFileTime = filemtime($cacheFile) - time();

        if ($cacheFileTime < ($minutes * 60) && $minutes != 0) {
            return '';
        }

        header("Cache-Control: max-age=" . $cacheFileTime);

        $message = json_decode(file_get_contents($cacheFile));

        return $message;
    }
            
    public function save($message, $pageId = 0)
    {
        $url = strtok($_SERVER["REQUEST_URI"], '?');
        
        $page = ($pageId > 0) ? '_' + $pageId : '';

        $filepath = 'Cache' . $url . $page . '.json';
        
        $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $filepath, $filepathMatches);
        if ($isInFolder) {
            $folderName = $filepathMatches[1];
            $fileName = $filepathMatches[2];
            if (!is_dir($folderName)) {
                mkdir($folderName, 0777, true);
            }
        }

        file_put_contents($filepath, json_encode($message));
    }

    public function delete($pageId = 0)
    {
        $url = strtok($_SERVER["REQUEST_URI"], '?');
        
        $page = ($pageId > 0) ? '_' + $pageId : '';

        $filepath = 'Cache' . $url . $page . '.json';
        
        unlink($filepath);
    }
}