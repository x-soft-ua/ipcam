<?php


namespace app;

class Core
{

    const IMG_DIR = '/home/xsoft/ipcam/www/img/';
    const CHECK_TIME = 1;

    /** @var array  */
    protected static $ipCamUrls = [
        'dom1' => 'http://192.168.0.10/cgi-bin/snapshot.cgi?1516360318329',
    ];

    /** @var IpCam[]  */
    private $imCams = [];

    public function __construct()
    {
        foreach (self::$ipCamUrls as $ipCamName => $ipCamUrl) {
            $this->imCams[$ipCamName] = new IpCam($ipCamUrl);
            $this->imCams[$ipCamName]->addZone(500, 300, 1000, 250);
            $this->imCams[$ipCamName]->addZone(500, 300, 1000, 520);
            $this->imCams[$ipCamName]->addZone(500, 600, 1000, 790);
            $this->imCams[$ipCamName]->addZone(500, 300, 600, 250);
            $this->imCams[$ipCamName]->addZone(500, 600, 600, 750);
            $this->imCams[$ipCamName]->addZone(600, 600, 0, 550);
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


    protected function task()
    {
        foreach ($this->imCams as $name => $ipCam) {
            $ipCam->ping();
            $ipCam->checkZones();
            $imgBuf = $ipCam->getAlertBuf();
            $this->saveImgBuf($imgBuf);
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
