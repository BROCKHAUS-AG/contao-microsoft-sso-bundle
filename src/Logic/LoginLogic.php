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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\BackendUser;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment as TwigEnvironment;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoginLogic
{
    private $framework;
    private $tokenStorage;
    private $twig;
    private $dispatcher;
    private $logger;
    private $requestStack;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack)
    {
        $this->framework = $framework;
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    private function usernameNotFound(string $username, UsernameNotFoundException $exception) : Response
    {
        $this->logger->log(
            LogLevel::WARNING,
            'User with username "'. $username. '" not found for auto login',
            ['contao' => new ContaoContext(__METHOD__, TL_ACCESS)]
        );

        return new Response($this->twig->render(
            '@BrockhausAgContaoMicrosoftSso/LoginState/loginFailed.html.twig',
            [
                'exception' => "User provider: ". $exception
            ]
        ));
    }

    private function getToken($user) : UsernamePasswordToken
    {
        $token = new UsernamePasswordToken($user, null, "contao_backend", $user->getRoles());
        $this->tokenStorage->setToken($token);
        return $token;
    }

    private function setTokenToContaoBackend($session, UsernamePasswordToken $token)
    {
        $session->set('_security_'. "contao_backend", serialize($token));
        $session->save();
    }

    private function toggleLoginEvent(UsernamePasswordToken $token)
    {
        $event = new InteractiveLoginEvent($this->requestStack->getCurrentRequest(), $token);
        $this->dispatcher->dispatch($event, 'security.interactive_login');
    }

    private function userWasSuccessfullyLoggedIn(string $username) : Response
    {
        $this->logger->log(
            LogLevel::INFO,
            'User "'. $username. '" was logged in automatically',
            ['contao' => new ContaoContext(__METHOD__, TL_ACCESS)]
        );

        return new Response($this->twig->render(
            '@BrockhausAgContaoMicrosoftSso/LoginState/loginSuccess.html.twig', []
        ));
    }

    public function loginUser(string $username) : Response
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $userProvider = new ContaoUserProvider($this->framework, $session, BackendUser::class, $this->logger);

        try {
            $user = $userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $exception) {
            return $this->usernameNotFound($username, $exception);
        }

        $token = $this->getToken($user);
        $this->setTokenToContaoBackend($session, $token);

        $this->toggleLoginEvent($token);

        return $this->userWasSuccessfullyLoggedIn($username);
    }
}