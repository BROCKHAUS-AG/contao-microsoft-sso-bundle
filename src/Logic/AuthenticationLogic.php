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
use BrockhausAg\ContaoMicrosoftSsoBundle\Model\User;
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

    private PasswordLogic $_passwordLogic;
    private DatabaseLogic $_databaseLogic;
    private LoginLogic $_loginLogic;
    private HttpLogic $_httpLogic;
    private TwigEnvironment $_twig;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                Connection $databaseConnection,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack,
                                string $path)
    {
        $this->_databaseLogic = new DatabaseLogic($databaseConnection);
        $this->_passwordLogic = new PasswordLogic();
        $this->_loginLogic = new LoginLogic($framework, $tokenStorage, $twig, $dispatcher, $logger, $requestStack,
            $path);
        $this->_twig = $twig;
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

    private function getUser($attributes): User
    {
        return new User(
            $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'][0],
            $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname'][0],
            $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname'][0]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
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

    /**
     * @throws Exception
     */
    private function insertOrUpdateUser(bool $result, User $user, int $admin) {
        $passwordHash = $this->_passwordLogic->newHashPassword();

        if (!$result) {
            $this->_databaseLogic->createUserInContaoDatabase($passwordHash, $user, $admin);
        }else {
            $this->_databaseLogic->updateUserInContaoDatabase($passwordHash, $user, $admin);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    private function updateUserData(User $user): void
    {
        $userIsInDatabase = $this->checkIfUserIsInDatabase($user->getUsername());

        $admin = $this->userIsInGroup($user->getUsername());

        $this->insertOrUpdateUser($userIsInDatabase, $user, $admin);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    private function updateMemberData(User $user): void
    {
        $memberIsInDatabase = $this->checkIfMemberIsInDatabase($user->getUsername());
        $this->insertOrUpdateMember($memberIsInDatabase, $user);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getUserAndUpdate(): User
    {
        $user = $this->getUser($_SESSION['samlUserdata']);
        $this->updateUserData($user);
        $this->updateMemberData($user);
        return $user;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function checkIfMemberIsInDatabase($username): bool
    {
        $statement = $this->_databaseLogic->loadMemberByUsername($username);
        return count($statement->fetchAllAssociative()) != 0;
    }

    /**
     * @throws Exception
     */
    private function insertOrUpdateMember(bool $memberIsInDatabase, User $user): void
    {
        $passwordHash = $this->_passwordLogic->newHashPassword();

        if (!$memberIsInDatabase) {
            $this->_databaseLogic->createMemberInContaoDatabase($passwordHash, $user);
        }else {
            $this->_databaseLogic->updateMemberInContaoDatabase($passwordHash, $user);
        }
    }

    private function destroySession(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    private function startSession(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_start();
        }
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

    private function processSamlRequestAndSaveCredentials(Auth $auth): void
    {
        $this->processSamlRequest($auth);
        $this->saveSAMLCredentialsToSession($auth);
    }

    private function displayUserSuccessfullyIdentified(): void
    {
        $email = $_SESSION['samlNameId'];
        echo '<h1>Identified user with SAML: ' . htmlentities($email) . '</h1>';
    }


    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function authenticate(Auth $auth, array $oauthCredentials, string $groupId) : Response
    {
        $this->_httpLogic = new HttpLogic($oauthCredentials, $groupId);
        $this->startSession();

        $this->processSamlRequestAndSaveCredentials($auth);
        $this->displayUserSuccessfullyIdentified();
        $user = $this->getUserAndUpdate();

        try {
            return $this->_loginLogic->login($user->getUsername());
        } catch (Exception $e) {
            return new Response($this->_twig->render(
                '@BrockhausAgContaoMicrosoftSso/LoginState/loginFailed.html.twig',
                [ 'exception' => $e ]
            ));
        } finally {
            $this->destroySession();
        }
    }
}