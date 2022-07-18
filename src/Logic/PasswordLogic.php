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

namespace BrockhausAg\ContaoMicrosoftSsoBundle\Logic;

class PasswordLogic {
    private function validateAndHashPassword(string $password) : string
    {
        $passwordLength = 8;
        if (strlen($password) < $passwordLength) {
            throw new InvalidArgumentException(
                sprintf('The password must be at least %s characters long.', $passwordLength)
            );
        }
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function randomPassword(): string
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $newPassword = "";
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, strlen($alphabet) - 1);
            $newPassword .= $alphabet[$n];
        }
        return $newPassword;
    }

    public function newHashPassword() : string
    {
        $password = $this->randomPassword();
        return $this->validateAndHashPassword($password);
    }
}