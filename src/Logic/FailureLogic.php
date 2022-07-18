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

use Contao\CoreBundle\Monolog\ContaoContext;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Twig\Environment as TwigEnvironment;

class FailureLogic
{
    private $twig;
    private $logger;

    public function __construct(TwigEnvironment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function usernameNotFound(string $username, UsernameNotFoundException $exception) : Response
    {
        $this->logger->log(
            LogLevel::WARNING,
            'Username "'. $username. '" not found for auto login',
            ['contao' => new ContaoContext(__METHOD__, TL_ACCESS)]
        );

        return new Response($this->twig->render(
            '@BrockhausAgContaoMicrosoftSso/LoginState/loginFailed.html.twig',
            [
                'exception' => "User provider: ". $exception
            ]
        ));
    }
}