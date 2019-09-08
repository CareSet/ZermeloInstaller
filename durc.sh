#!/bin/bash
php artisan DURC:mine --squash --DB=REPLACE_ME
php artisan DURC:write  
cp routes/starting.web.php routes/web.php
cat routes/web.durc.php | tail -n +2 >> routes/web.php
cat routes/ending.web.php >> routes/web.php
composer clear-cache
composer dump-autoload
