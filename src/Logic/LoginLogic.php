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
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\HttpFoundation\Response;

class LoginLogic
{
    private TwigEnvironment $_twig;
    private UserLoginLogic $_userLoginLogic;
    private MemberLoginLogic $_memberLoginLogic;
    private string $_path;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack,
                                string $path)
    {
        $this->_twig = $twig;
        $this->_path = $path;
        $this->_userLoginLogic = new UserLoginLogic($framework, $tokenStorage, $twig, $dispatcher, $logger,
           $requestStack);
        $this->_memberLoginLogic = new MemberLoginLogic($framework, $tokenStorage, $twig, $dispatcher, $logger,
            $requestStack);
    }

    private function getLoginTypeFromFile(): string
    {
        return file_get_contents($this->_path. Constants::LOGIN_TYPE_FILE);
    }

    /**
     * @throws Exception
     */
    public function login(string $username): Response
    {
        $loginType = $this->getLoginTypeFromFile();
        if ($loginType == Constants::USER_LOGIN) {
            return $this->_userLoginLogic->login($username);
        }else if ($loginType == Constants::MEMBER_LOGIN) {
            return $this->_memberLoginLogic->login($username);
        }

        return new Response($this->_twig->render(
            '@BrockhausAgContaoMicrosoftSso/LoginState/loginFailed.html.twig',
            [
                'exception' => "\"$loginType\" is an invalid login type."
            ]
        ));
    }
}