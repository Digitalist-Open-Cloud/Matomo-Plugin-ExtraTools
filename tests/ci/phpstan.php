<?php

define('PIWIK_INCLUDE_PATH', '/var/www/html');
function _glob($pattern, $flags = 0)
{
    return glob($pattern, $flags);
}
