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

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\FrontendUser;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Twig\Environment as TwigEnvironment;

class MemberLoginLogic
{
    private ContaoFramework $_framework;
    private TokenStorageInterface $_tokenStorage;
    private TwigEnvironment $_twig;
    private EventDispatcherInterface $_dispatcher;
    private LoggerInterface $_logger;
    private RequestStack $_requestStack;
    private FailureLogic $_failureLogic;

    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack)
    {
        $this->_framework = $framework;
        $this->_tokenStorage = $tokenStorage;
        $this->_twig = $twig;
        $this->_dispatcher = $dispatcher;
        $this->_logger = $logger;
        $this->_requestStack = $requestStack;
        $this->_failureLogic = new FailureLogic($twig, $logger);
    }

    private function getToken($user) : UsernamePasswordToken
    {
        $token = new UsernamePasswordToken($user, null, "frontend", $user->getRoles());
        $this->_tokenStorage->setToken($token);
        return $token;
    }

    private function setTokenToContaoBackend($session, UsernamePasswordToken $token)
    {
        $session->set('_security_'. "frontend", serialize($token));
        $session->save();
    }

    private function toggleLoginEvent(UsernamePasswordToken $token)
    {
        $event = new InteractiveLoginEvent($this->_requestStack->getCurrentRequest(), $token);
        $this->_dispatcher->dispatch($event, 'security.interactive_login');
    }

    /**
     * @throws Exception
     */
    private function userWasSuccessfullyLoggedIn(string $username) : Response
    {
        $this->_logger->log(
            LogLevel::INFO,
            'User "'. $username. '" was logged in automatically',
            ['contao' => new ContaoContext(__METHOD__, 'TL_ACCESS')]
        );

        return new Response($this->_twig->render(
            '@BrockhausAgContaoMicrosoftSso/LoginState/loginMemberSuccess.html.twig', []
        ));
    }

    /**
     * @throws Exception
     */
    public function login(string $username): Response
    {
        $session = $this->_requestStack->getCurrentRequest()->getSession();
        $userProvider = new ContaoUserProvider($this->_framework, $session, FrontendUser::class, $this->_logger);

        try {
            $user = $userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $exception) {
            return $this->_failureLogic->usernameNotFound($username, $exception);
        }

        $token = $this->getToken($user);
        $this->setTokenToContaoBackend($session, $token);

        $this->toggleLoginEvent($token);

        return $this->userWasSuccessfullyLoggedIn($username);
    }
}