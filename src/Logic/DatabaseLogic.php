<?php

declare(strict_types=1);

/*
 * This file is part of Contao Microsoft SSO Bundle.
 *
 * (c) BROCKHAUS AG 2021 <info@brockhaus-ag.de>
 * Author Niklas Lurse (INoTime) <nlurse@brockhaus-ag.de>
 *
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/BROCKHAUS-AG/contao-microsoft-sso-bundle
 */

namespace BrockhausAg\ContaoMicrosoftSsoBundle\Logic;

use Doctrine\DBAL\Connection;

class DatabaseLogic {
    private $databaseConnection;

    public function __construct(Connection $databaseConnection) {
        $this->databaseConnection = $databaseConnection;
    }

    public function updateUserInContaoDatabase(string $hash, string $firstname, string $lastname,
                                                string $username, int $admin) : void {
        $this->databaseConnection->createQueryBuilder()
            ->update("tl_user")
            ->set("password", ":password")
            ->set("name", ":name")
            ->set("language", ":language")
            ->set("email", ":email")
            ->set("admin", ":admin")
            ->set("et_enable", ":et_enable")
            ->where("username =:username")
            ->setParameter("password", $hash)
            ->setParameter("name", $firstname. " ". $lastname)
            ->setParameter("language", "de")
            ->setParameter("email", $username)
            ->setParameter("admin", $admin)
            ->setParameter("et_enable", 1)
            ->setParameter("username", $username)
            ->execute();
    }

    public function createUserInContaoDatabase(string $hash, string $firstname, string $lastname,
                                                string $username, int $admin) : void {
        $this->databaseConnection->createQueryBuilder()
            ->insert("tl_user")
            ->values(
                [
                    "tstamp" => "?",
                    "password" => "?",
                    "name" => "?",
                    "language" => "?",
                    "email" => "?",
                    "admin" => "?",
                    "et_enable" => "?",
                    "username" => "?"
                ]
            )
            ->setParameters(
                [
                    0 => time(),
                    1 => $hash,
                    2 => $firstname. " ". $lastname,
                    3 => "de",
                    4 => $username,
                    5 => $admin,
                    6 => 1,
                    7 => $username
                ]
            )
            ->execute();
    }

    public function loadUserByUsername(string $username)
    {
        return $this->databaseConnection->createQueryBuilder()
            ->select('*')
            ->from('tl_user')
            ->where('username =:username')
            ->setParameter('username', $username)
            ->execute();
    }
}