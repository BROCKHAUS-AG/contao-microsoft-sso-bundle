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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;
use Twig\Environment as TwigEnvironment;
use OneLogin\Saml2\Auth;
use Psr\Log\LoggerInterface;

class AdfsLogic {
    private TwigEnvironment $_twig;
    private IOLogic $_ioLogic;
    private AuthenticationLogic $_authenticationLogic;

    private array $oauthCredentials;
    private string $groupId;
    private string $_path;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                Connection $databaseConnection,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack,
                                string $path)
    {
        $this->_twig = $twig;
        $this->_path = $path;

        $this->_ioLogic = new IOLogic($logger, $path);
        $this->_authenticationLogic = new AuthenticationLogic($framework, $tokenStorage, $twig, $databaseConnection,
            $dispatcher, $logger, $requestStack, $path);
    }

    /**
     * @throws Exception
     */
    private function createLoginTypeFile(string $file, string $loginType): void
    {
        if (!file_exists($file)) {
            file_put_contents($file, $loginType);
        }
    }

    private function deleteLoginTypeFile(string $file): void
    {
        unlink($file);
    }


    /**
     * Generate the response
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function generateResponse(string $loginType) : Response
    {
        $file = $this->_path. Constants::LOGIN_TYPE_FILE;
        $this->createLoginTypeFile($file, $loginType);

        require_once(__DIR__ . "/../Resources/_toolkit_loader.php");
        $this->deleteCookies();

        $settings = $this->_ioLogic->loadSAMLSettings();
        $auth = new Auth($settings);

        if (!empty($_POST)) {
            $this->loadAuthConfig();
            $result = $this->_authenticationLogic->authenticate($auth, $this->oauthCredentials, $this->groupId);
            $this->deleteLoginTypeFile($file);
            return $result;
        }

        $auth->login();
        return new Response($this->_twig->render(
            '@BrockhausAgContaoMicrosoftSso/Adfs/adfs.html.twig', []
        ));
    }

    private function loadAuthConfig()
    {
        $config = $this->_ioLogic->loadAuthConfig();
        $this->oauthCredentials = $config[0];
        $this->groupId = $config[1];
    }

    private function deleteCookies()
    {
        if (isset($_COOKIE['csrf_https-contao_csrf_token'])) {
            setcookie("csrf_https-contao_csrf_token", "", time() - 3600);
        }

        if (isset($_COOKIE['PHPSESSID'])) {
            setcookie("PHPSESSID", "", time() - 3600);
        }
    }
}
