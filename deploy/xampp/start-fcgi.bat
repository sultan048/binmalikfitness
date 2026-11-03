@echo off
rem Keeps a PHP 8.4 FastCGI worker listening for the BIN MALIK FITNESS Apache vhost.
rem Run this before opening http://binmalikfitness.localhost:8090 and leave the window open.
set PHP_FCGI_MAX_REQUESTS=0
set PHPRC=C:\xampp\php84
:loop
"C:\xampp\php84\php-cgi.exe" -b 127.0.0.1:9084
goto loop
