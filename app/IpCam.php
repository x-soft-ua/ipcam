<?php

namespace app;

class IpCam
{

    const LOG_LEVEL = self::LOG_ALERT;
    const ALERT_DELAY = 5;

    const LOG_INFO = 0;
    const LOG_ALERT = 1;
    const FUZZ_FACTOR = 0.09;
    const BUF_LIM = 5;

    /** @var string */
    private $url;

    /** @var array */
    private $buf = [];

    /** @var array  */
    private $zones = [];

    /** @var array */
    private $alertZones = [];

    /** @var array */
    private $alertBuf = [];

    /** @var int  */
    private $alertDelay = 0;



    public function __construct(String $url)
    {
        $this->url = $url;
    }

    public function log(String $msg, $level = self::LOG_INFO)
    {
        if ($level >= self::LOG_LEVEL) {
            echo date(DATE_ATOM) . ': ' . $msg . PHP_EOL;
        }
    }

    public function addZone(int $w, int $h, int $ox, int $oy, $fuzzFactor = self::FUZZ_FACTOR)
    {
        $this->zones[] = [$w, $h, $ox, $oy, $fuzzFactor];
    }

    public function getAlertBuf()
    {
        if (!empty($this->alertBuf)) {
            return $this->alertBuf;
        }
        return [];
    }

    public function ping()
    {
        $data = file_get_contents($this->url);

        if (empty($data)) {
            throw new \Exception('Error while file fetching');
        }

        $this->buf[time()] = $data;
        if (count($this->buf) > self::BUF_LIM) {
            unset($this->buf[array_keys($this->buf)[0]]);
        }

        $this->log('Buf: ' . count($this->buf));
    }

    public function checkZones()
    {
        if (count($this->buf) < 2) {
            return false;
        }

        $checkResult = [];
        $zones = empty($this->zones) ? [[1920, 1080, 0, 0, self::FUZZ_FACTOR]] : $this->zones;

        $bufKeys = array_keys($this->buf);
        $origImg = $this->buf[$bufKeys[0]];
        $checkImg = $this->buf[$bufKeys[count($this->buf)-1]];

        $imageOrig = new \Imagick();
        $imageOrig->setOption('fuzz', '2%');
        $imageCheck = new \Imagick();


        foreach ($zones as $zoneId => $zone) {
            $imageOrig->readImageBlob($origImg);
            $imageCheck->readImageBlob($checkImg);

            $imageOrig->cropImage($zone[0], $zone[1], $zone[2], $zone[3]);
            $imageCheck->cropImage($zone[0], $zone[1], $zone[2], $zone[3]);

            @unlink('/home/xsoft/ipcam/www/img/tmp/id_' . $zoneId . '.jpg');
            $imageOrig->writeImage('/home/xsoft/ipcam/www/img/tmp/id_' . $zoneId . '.jpg');

            $result = $imageOrig->compareImages($imageCheck, 9);
            $checkResult[$zoneId] = $result[1];


            //$imageOrig->writeImage('./' . $zoneId . '.jpg');
        }

        $alert = false;

        foreach ($checkResult as $zoneId => $zoneValue) {
            if ($zoneValue >= self::FUZZ_FACTOR) {
                $this->alertZones[$zoneId] = $zones[$zoneId];

                $this->alertBuf[time()] = [
                    'img' => $checkImg,
                    'zones' => $this->alertZones
                ];

                $this->log('Zone #' . $zoneId . ' [' . $zoneValue . '] diff', self::LOG_ALERT);
                $alert = true;
            }
        }

        if ($alert) {
            $this->alertDelay = self::ALERT_DELAY;
        }

        if ($this->alertDelay > 0) {
            $this->alertDelay--;
            foreach ($this->buf as $bufImgTs => $bufImg) {
                $this->alertBuf[$bufImgTs] = [
                    'img' => $bufImg,
                    'zones' => $this->alertZones
                ];
            }
            $alert = true;
        } else {
            $this->alertZones = [];
            $this->alertBuf = [];
        }

        $imageOrig->destroy();
        $imageCheck->destroy();

        return $alert;
    }
}