<?php

require 'vendor/autoload.php';

if (!class_exists(\Nails\Housekeeping\Routine\Base::class)) {
    require __DIR__ . '/Stub/Housekeeping.php';
}

if (!function_exists('deprecatedError')) {
    function deprecatedError($sMethod, $sUseInstead = '')
    {
        $sError = 'Function ' . $sMethod . ' is deprecated.';
        if (!empty($sUseInstead)) {
            $sError .= ' Use "' . $sUseInstead . '" instead.';
        }
        trigger_error($sError, E_USER_DEPRECATED);
    }
}

if (!function_exists('appSetting')) {
    function appSetting(?string $sKey = null, string $sGrouping = 'app', $mDefault = null, $bForceRefresh = false)
    {
        return $mDefault;
    }
}
