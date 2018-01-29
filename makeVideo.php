<?php

//ffmpeg -r 24 -pattern_type glob -i './img/*.jpg' -s hd1080 -vcodec libx264 timelapse.mp4

$contentDir = '/home/xsoft/ipcam/www/img';
$videoDir = '/home/xsoft/ipcam/www/video';

@mkdir($videoDir);
$dirs = scandir($contentDir);


unset($dirs[0], $dirs[1]);
foreach ($dirs as $dir) {
    if ($dir == date('Y-m-d')) {
        echo 'Skip current day ' . $dir . PHP_EOL;
        continue;
    }
    $dateTs = strtotime($dir);
    if (!empty((int)$dateTs)) {
        $outFile = $videoDir . '/' . $dir . '.mp4';
        if (file_exists($outFile)) {
            echo 'Exists: ' . $outFile . PHP_EOL;
            continue;
        }
        $cmd = 'ffmpeg -r 30 -pattern_type glob -i \'' . $contentDir . '/' . $dir . '/*.jpg\' -s hd1080 -vcodec libx264 ' . $outFile;
        echo $cmd . PHP_EOL;
        shell_exec($cmd);
    }
}
