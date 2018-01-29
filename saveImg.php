<?php


function saveImgBuf(&$buf = [])
{
    foreach ($buf as $time => $imgStr) {
        $contentDir = '/home/xsoft/ipcam/www/';
        $imgDir = $contentDir . 'img/' . date('Y-m-d', (int)$time);
        if (!file_exists($imgDir)) {
            mkdir($imgDir);
        }
        $imgSrc = $imgDir . '/' . date('Y_m_d__H_i_s', (int)$time) . '.jpg';
        //echo $imgSrc . PHP_EOL;
        file_put_contents($imgSrc, $imgStr);
        unset($buf[$time]);
    }
}

$bufCount = 2;
$buf = [];



$counter = 0;
$startTime = time();
$image = new Imagick();
$image->SetOption('fuzz', '2%');
$prevImg = null;
$lastCompare = 0;
$save = 0;

while(true) {
    $startTs = microtime(1);
    $counter++;
//    echo $counter . PHP_EOL;

    $saveBuf = false;

    $url = 'http://192.168.0.10/cgi-bin/snapshot.cgi?1516360318329';
    $data = file_get_contents($url);

    if (empty($data)) {
        echo 'Error while file fetching' . PHP_EOL;
        sleep(1);
        continue;
    }

    $buf[time()] = $data;
    if (count($buf) > $bufCount) {
        foreach ($buf as $k => $v) {
            unset($buf[$k]);
            break;
        }
    }


    $image->readImageBlob($data);
    if (!empty($prevImg) && $counter > 5) {
        $result = $image->compareImages($prevImg, 9);
        $comp = ($lastCompare - $result[1]) ;

        if (($lastCompare - $result[1])>0.007 || -1*($lastCompare - $result[1])>0.007) {
            echo  date(DATE_ATOM) . ': ' .($lastCompare - $result[1]) . PHP_EOL;
            $save = 4;
        }

        $lastCompare = $result[1];
    }
    if ($prevImg instanceof Imagick) {
        $prevImg->destroy();
    }
    $prevImg = clone $image;
    $image->destroy();



    if ($save>0) {
        $save--;
        echo 'Save buf: ' . count($buf) . ' imgs' . PHP_EOL;
        saveImgBuf($buf);
    }
    usleep(10000);

    $ts = microtime(1) - $startTs;
    echo $comp . PHP_EOL;
}

?>
