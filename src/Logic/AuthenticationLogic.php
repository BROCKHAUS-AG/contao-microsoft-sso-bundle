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

use BrockhausAg\ContaoMicrosoftSsoBundle\Constants;
use Contao\CoreBundle\Framework\ContaoFramework;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment as TwigEnvironment;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use OneLogin\Saml2\Auth;

class AuthenticationLogic {

    private $_passwordLogic;
    private $_databaseLogic;
    private $_loginLogic;
    private $_httpLogic;
    private $twig;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                Connection $databaseConnection,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack)
    {
        $this->_databaseLogic = new DatabaseLogic($databaseConnection);
        $this->_passwordLogic = new PasswordLogic();
        $this->_loginLogic = new LoginLogic($framework, $tokenStorage, $twig, $dispatcher, $logger, $requestStack);
        $this->twig = $twig;
    }

    private function saveSAMLCredentialsToSession(Auth $auth)
    {
        $_SESSION['samlUserdata'] = $auth->getAttributes();
        $_SESSION['samlNameId'] = $auth->getNameId();
        $_SESSION['samlNameIdFormat'] = $auth->getNameIdFormat();
        $_SESSION['samlSessionIndex'] = $auth->getSessionIndex();
    }

    private function checkIfUserIsInGroup($groupMembers, string $username) : bool
    {
        foreach($groupMembers as $groupMember){
            if(strtolower($groupMember["userPrincipalName"]) == strtolower($username)){
                echo "User found in group: ".$groupMember["userPrincipalName"]."<br>";
                return true;
            }
        }
        return false;
    }

    private function getUserData($attributes) : array
    {
        return array(
            "username" => $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'][0],
            "firstname" => $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname'][0],
            "lastname" => $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname'][0]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function checkIfUserIsInDatabase(string $username) : bool
    {
        $statement = $this->_databaseLogic->loadUserByUsername($username);
        return $statement->fetchAllAssociative() != null;
    }

    private function loadGroupMembers() : array
    {
        $accessToken = $this->_httpLogic->getAccessToken();
        return $this->_httpLogic->getGroupMembersWithAccessToken($accessToken);
    }

    private function userIsInGroup(string $username) : int
    {
        $groupMembers = $this->loadGroupMembers();
        $isInGroup = $this->checkIfUserIsInGroup($groupMembers, $username);
        return $isInGroup ? 1 : 0;
    }

    private function insertOrUpdateUser(bool $result, array $userData, int $admin) {
        $passwordHash = $this->_passwordLogic->newHashPassword();

        if (!$result) {
            $this->_databaseLogic->createUserInContaoDatabase($passwordHash, $userData["firstname"],
                $userData["lastname"], $userData["username"], $admin);
        }else {
            $this->_databaseLogic->updateUserInContaoDatabase($passwordHash, $userData["firstname"],
                $userData["lastname"], $userData["username"], $admin);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function updateUserData($userData): void
    {
        $userIsInDatabase = $this->checkIfUserIsInDatabase($userData["username"]);

        $admin = $this->userIsInGroup($userData["username"]);

        $this->insertOrUpdateUser($userIsInDatabase, $userData, $admin);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function updateMemberData($userData): void
    {
        $memberIsInDatabase = $this->checkIfMemberIsInDatabase($userData["username"]);
        $this->insertOrUpdateMember($memberIsInDatabase, $userData);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function checkIfMemberIsInDatabase($username): bool
    {
        $statement = $this->_databaseLogic->loadMemberByUsername($username);
        return count($statement->fetchAllAssociative()) != 0;
    }

    private function insertOrUpdateMember(bool $memberIsInDatabase, $userData): void
    {
        $passwordHash = $this->_passwordLogic->newHashPassword();

        if (!$memberIsInDatabase) {
            $this->_databaseLogic->createMemberInContaoDatabase($passwordHash, $userData["firstname"],
                $userData["lastname"], $userData["username"]);
        }else {
            $this->_databaseLogic->updateMemberInContaoDatabase($passwordHash, $userData["firstname"],
                $userData["lastname"], $userData["username"]);
        }
    }

    private function destroySession()
    {
        $_SESSION = [];
        session_destroy();
    }

    private function getAuthNRequestID() : ?string
    {
        if (isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
            return $_SESSION['AuthNRequestID'];
        }
        return null;
    }

    private function processSamlRequest(Auth $auth)
    {
        $requestID = $this->getAuthNRequestID();
        $auth->processResponse($requestID);
        unset($_SESSION['AuthNRequestID']);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function authenticate(Auth $auth, array $oauthCredentials, string $groupId) : Response
    {
        $this->_httpLogic = new HttpLogic($oauthCredentials, $groupId);

        $this->processSamlRequest($auth);
        $this->saveSAMLCredentialsToSession($auth);

        $email = $_SESSION['samlNameId'];
        echo '<h1>Identified user with SAML: ' . htmlentities($email) . '</h1>';

        $userData = $this->getUserData($_SESSION['samlUserdata']);
        $this->updateUserData($userData);
        $this->updateMemberData($userData);

        try {
            return $this->_loginLogic->login($userData["username"]);
        } catch (Exception $e) {
            return new Response($this->twig->render(
                '@BrockhausAgContaoMicrosoftSso/LoginState/loginFailed.html.twig',
                [
                    'exception' => $e
                ]
            ));
        } finally {
            $this->destroySession();
        }
    }
}