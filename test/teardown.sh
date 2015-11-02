php dropcomments.php
php dropimages.php
php dropusers.php

shopt -s extglob
cd ../resource/img
rm !(.htaccess)
cd ./thumb
rm !(.htaccess)
