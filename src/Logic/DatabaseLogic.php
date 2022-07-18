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

    public function loadMemberByUsername($username)
    {
        return $this->databaseConnection->createQueryBuilder()
            ->select('*')
            ->from('tl_member')
            ->where('username =:username')
            ->setParameter('username', $username)
            ->execute();
    }

    public function createMemberInContaoDatabase(string $passwordHash, string $firstname, string $lastname,
                                                 string $username)
    {
        $this->databaseConnection->createQueryBuilder()
            ->insert("tl_member")
            ->values(
                [
                    "tstamp" => "?",
                    "firstname" => "?",
                    "lastname" => "?",
                    "country" => "?",
                    "email" => "?",
                    "login" => "?",
                    "username" => "?",
                    "password" => "?"
                ]
            )
            ->setParameters(
                [
                    0 => time(),
                    1 => $firstname,
                    2 => $lastname,
                    3 => "de",
                    4 => $username,
                    5 => 1,
                    6 => $username,
                    7 => $passwordHash
                ]
            )->execute();
    }

    public function updateMemberInContaoDatabase(string $passwordHash, string $firstname, string $lastname,
                                                 string $username)
    {
        $this->databaseConnection->createQueryBuilder()
            ->update("tl_member")
            ->set("firstname", ":firstname")
            ->set("lastname", ":lastname")
            ->set("email", ":email")
            ->set("username", ":username")
            ->set("password", ":password")
            ->where("username =:username")
            ->setParameter("firstname", $firstname)
            ->setParameter("lastname", $lastname)
            ->setParameter("email", $username)
            ->setParameter("username", $username)
            ->setParameter("password", $passwordHash)
            ->execute();
    }
}