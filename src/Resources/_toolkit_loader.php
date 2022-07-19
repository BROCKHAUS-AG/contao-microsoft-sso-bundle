<?php

declare(strict_types=1);
/*
 * This file is part of Contao Microsoft SSO Bundle.
 *
 * (c) BROCKHAUS AG 2021 <info@brockhaus-ag.de>
 *
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/BROCKHAUS-AG/contao-microsoft-sso-bundle
 */

class ToolkitLoader {
    public function __construct()
    {
        if (file_exists('vendor/autoload.php')) {
            require 'vendor/autoload.php';
        }

        $xmlseclibs = __DIR__. "/extlib/xmlseclibs/";
        $folderInfo = scandir($xmlseclibs);
        $this->loadFiles($xmlseclibs, $folderInfo);

        $utils = __DIR__. "/extlib/xmlseclibs/Utils/";
        $folderInfo = scandir($utils);
        $this->loadFiles($utils, $folderInfo);

        $libDir = __DIR__ . '/extlib/Saml2/';
        $folderInfo = scandir($libDir);
        $this->loadFiles($libDir, $folderInfo);
    }

    public function loadFiles($path, $folderInfo)
    {
        foreach ($folderInfo as $element) {
            if (is_file($path.$element) && (substr($element, -4) === '.php')) {
                include_once $path.$element;
            }
        }
    }
}

$loader = new ToolkitLoader();