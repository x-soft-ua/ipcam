<?php

while (1) {
    $data = shell_exec('ps aux | grep php');
    $needExec = strpos($data, 'saveImg.php') === false;


    if ($needExec) {
        shell_exec('/usr/bin/nohup /usr/bin/php /home/xsoft/ipcam/saveImg.php >/home/xsoft/watchdog.log 2>&1 &');
        echo 'Executed!' . PHP_EOL;
    } else {
        echo 'No need to exec' . PHP_EOL;
    }
    sleep(1);
}