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

use BrockhausAg\ContaoMicrosoftSsoBundle\Model\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class DatabaseLogic {
    private Connection $_databaseConnection;

    public function __construct(Connection $databaseConnection) {
        $this->_databaseConnection = $databaseConnection;
    }

    /**
     * @throws Exception
     */
    public function updateUserInContaoDatabase(string $hash, User $user, int $admin) : void {
        $this->_databaseConnection->createQueryBuilder()
            ->update("tl_user")
            ->set("password", ":password")
            ->set("name", ":name")
            ->set("language", ":language")
            ->set("email", ":email")
            ->set("admin", ":admin")
            ->where("username =:username")
            ->setParameter("password", $hash)
            ->setParameter("name", $user->getFirstname(). " ". $user->getLastname())
            ->setParameter("language", "de")
            ->setParameter("email", $user->getUsername())
            ->setParameter("admin", $admin)
            ->setParameter("username", $user->getUsername())
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function createUserInContaoDatabase(string $hash, User $user, int $admin) : void {
        $this->_databaseConnection->createQueryBuilder()
            ->insert("tl_user")
            ->values(
                [
                    "tstamp" => "?",
                    "password" => "?",
                    "name" => "?",
                    "language" => "?",
                    "email" => "?",
                    "admin" => "?",
                    "username" => "?"
                ]
            )
            ->setParameters(
                [
                    0 => time(),
                    1 => $hash,
                    2 => $user->getFirstname(). " ". $user->getLastname(),
                    3 => "de",
                    4 => $user->getUsername(),
                    5 => $admin,
                    6 => $user->getUsername()
                ]
            )
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function loadUserByUsername(string $username)
    {
        return $this->_databaseConnection->createQueryBuilder()
            ->select('*')
            ->from('tl_user')
            ->where('username =:username')
            ->setParameter('username', $username)
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function loadMemberByUsername($username)
    {
        return $this->_databaseConnection->createQueryBuilder()
            ->select('*')
            ->from('tl_member')
            ->where('username =:username')
            ->setParameter('username', $username)
            ->execute();
    }

    /**
     * @throws Exception
     */
    public function createMemberInContaoDatabase(string $passwordHash, User $user)
    {
        $this->_databaseConnection->createQueryBuilder()
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
                    1 => $user->getFirstname(),
                    2 => $user->getLastname(),
                    3 => "de",
                    4 => $user->getUsername(),
                    5 => 1,
                    6 => $user->getUsername(),
                    7 => $passwordHash
                ]
            )->execute();
    }

    /**
     * @throws Exception
     */
    public function updateMemberInContaoDatabase(string $passwordHash, User $user)
    {
        $this->_databaseConnection->createQueryBuilder()
            ->update("tl_member")
            ->set("firstname", ":firstname")
            ->set("lastname", ":lastname")
            ->set("email", ":email")
            ->set("username", ":username")
            ->set("password", ":password")
            ->where("username =:username")
            ->setParameter("firstname", $user->getFirstname())
            ->setParameter("lastname", $user->getLastname())
            ->setParameter("email", $user->getUsername())
            ->setParameter("username", $user->getUsername())
            ->setParameter("password", $passwordHash)
            ->execute();
    }
}