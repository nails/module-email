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

if (!interface_exists(\Nails\FindReplace\Interfaces\Exclusion::class, false)) {
    foreach ([
        dirname(__DIR__) . '/vendor/nails/common/src/FindReplace/Interfaces/Exclusion.php',
        dirname(__DIR__, 2) . '/common/src/FindReplace/Interfaces/Exclusion.php',
    ] as $nailsFindReplaceExclusion) {
        if (is_file($nailsFindReplaceExclusion)) {
            require_once $nailsFindReplaceExclusion;
            break;
        }
    }
}
