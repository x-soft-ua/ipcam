<?php


namespace app;

class Core
{

    const IMG_DIR = '/home/xsoft/ipcam/www/img/';
    const VIDEO_DIR = '/home/xsoft/ipcam/www/video/';
    const CHECK_TIME = 1;

    const IMAGES_EXPIRE = 2; /* days */
    const VIDEO_EXPIRE = 5; /* days */

    /** @var array  */
    protected static $ipCamUrls = [
        'dom1' => 'http://192.168.0.1:8001/cgi-bin/snapshot.cgi?1516360318329',
    ];

    /** @var IpCam[]  */
    private $imCams = [];

    public function __construct()
    {
        foreach (self::$ipCamUrls as $ipCamName => $ipCamUrl) {
            $this->imCams[$ipCamName] = new IpCam($ipCamUrl);
            $this->imCams[$ipCamName]->addZone(450, 300, 1050, 50);
            $this->imCams[$ipCamName]->addZone(500, 300, 1000, 250);
            $this->imCams[$ipCamName]->addZone(500, 300, 1000, 520);
            $this->imCams[$ipCamName]->addZone(500, 600, 1000, 790);
//            $this->imCams[$ipCamName]->addZone(500, 300, 600, 250);
            $this->imCams[$ipCamName]->addZone(900, 600, 800, 750);
            $this->imCams[$ipCamName]->addZone(600, 600, 0, 450);
        }
    }

    public function init()
    {
        while(true) {
            $startTs = microtime(1);
            $this->task();
            $endTs = microtime(1);
            $restTs = $endTs - $startTs;
            if ($restTs < self::CHECK_TIME) {
                $restTs = (self::CHECK_TIME - $restTs) * 1000000;
                usleep((int)$restTs);
            }

            //$finalTs = (microtime(1) - $startTs) . PHP_EOL;
        }
    }

    protected function delTree($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object)) {
                        rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    protected function task()
    {
        $this->cleanDir();
        foreach ($this->imCams as $name => $ipCam) {
            $ipCam->ping();
            $ipCam->checkZones();
            $imgBuf = $ipCam->getAlertBuf();
            $this->saveImgBuf($imgBuf);
        }
    }
    
    protected function cleanDir()
    {
        $imageDirs = scandir(self::IMG_DIR);
        $matched = [];
        
        foreach ($imageDirs as $imageDir) {
            if (preg_match('/^\d+-\d+-\d+$/i', $imageDir, $matched)) {
                if (strtotime($matched[0]) < (time() - self::IMAGES_EXPIRE * 3600 * 24)) {
                    echo 'remove dir: ' . self::IMG_DIR . $imageDir . PHP_EOL;
                    $this->delTree(self::IMG_DIR . $imageDir);   
                }
            }
        }
        
        $videoFiles = scandir(self::VIDEO_DIR);
        $matched = [];
        foreach ($videoFiles as $videoFile) {
            if (preg_match('/^(\d+-\d+-\d+)\.mp4$/i', $videoFile, $matched)) {
                if (strtotime($matched[1]) < (time() - self::VIDEO_EXPIRE * 3600 * 24)) {
                    echo 'remove file: ' . self::VIDEO_DIR . $videoFile . PHP_EOL;
                    unlink(self::VIDEO_DIR . $videoFile);
                }
            }
        }
    }


    protected function saveImgBuf(Array $imgBuf)
    {
        if (empty($imgBuf)) {
            return false;
        }

        foreach ($imgBuf as $time => $imgData) {
            $this->saveImage($time, $imgData);
        }
    }


    protected function saveImage($time, $imgData)
    {
        $imgDir = self::IMG_DIR . date('Y-m-d', (int)$time);
        if (!file_exists($imgDir)) {
            mkdir($imgDir, 0777, true);
        }

        $imgSrc = $imgDir . '/' . date('Y_m_d__H_i_s', (int)$time) . '.jpg';
        file_put_contents($imgSrc, $imgData['img']);
    }
}
