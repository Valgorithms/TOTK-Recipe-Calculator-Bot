chcp 65001
@echo off
cls
set watch=TOTK
title %watch% Watchdog
:watchdog
echo (%time%) %watch% started.
php -dopcache.cache_id=2 -dopcache.enable_cli=1 -dopcache.jit_buffer_size=264M "main.php" > botlog.txt
echo (%time%) %watch% closed or crashed, restarting.
goto watchdog