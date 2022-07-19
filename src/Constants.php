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

namespace BrockhausAg\ContaoMicrosoftSsoBundle;

abstract class Constants
{
    public const USER_LOGIN = "USER";
    public const MEMBER_LOGIN = "MEMBER";

    public const LOGIN_TYPE_FILE = "/contao_microsoft_sso_bundle-login_type.tmp";

    public const SETTINGS_PATH = "/settings/brockhaus-ag/contao-microsoft-sso-bundle/";
}