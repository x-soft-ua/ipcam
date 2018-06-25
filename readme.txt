crontab:
0 * * * * /usr/bin/php /home/xsoft/ipcam/makeVideo.php

supervisior:
[program:ipcam]
autostart = true
autorestart = true
command =  bash -c "/usr/bin/php /home/xsoft/ipcam/ipcam.php; sleep 1"
#environment=SECRET_ID="secret_id",SECRET_KEY="secret_key_avoiding_%_chars"
stdout_logfile = /var/log/ipcam.log
stderr_logfile = /var/log/ipcam.err.log
startretries = 1000
user = root

