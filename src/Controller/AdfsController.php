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

namespace BrockhausAg\ContaoMicrosoftSsoBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;
use Twig\Environment as TwigEnvironment;
use Psr\Log\LoggerInterface;

use BrockhausAg\ContaoMicrosoftSsoBundle\Logic\AdfsLogic;

/**
 * Class AdfsController
 *
 * @Route("/adfs",
 *     name="brockhaus_ag_contao_microsoft_sso_adfs",
 *     defaults={
 *         "_scope" = "frontend",
 *         "_token_check" = true
 *     }
 * )
 */
class AdfsController extends AbstractController
{
    private $_adfs;

    /**
     * AdfsController constructor.
     */
    public function __construct(ContaoFramework $framework,
                                TokenStorageInterface $tokenStorage,
                                TwigEnvironment $twig,
                                Connection $databaseConnection,
                                EventDispatcherInterface $dispatcher,
                                LoggerInterface $logger,
                                RequestStack $requestStack)
    {
        $this->_adfs = new AdfsLogic($framework, $tokenStorage, $twig, $databaseConnection, $dispatcher, $logger,
            $requestStack);
    }

    /**
     * Generate the response
     */
    public function __invoke() : Response
    {
        return $this->_adfs->generateResponse();
    }
}
