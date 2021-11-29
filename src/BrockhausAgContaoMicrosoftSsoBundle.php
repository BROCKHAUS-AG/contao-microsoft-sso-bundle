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

namespace BrockhausAg\ContaoMicrosoftSsoBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class BrockhausAgContaoMicrosoftSsoBundle
 */
class BrockhausAgContaoMicrosoftSsoBundle extends Bundle
{
	/**
	 * {@inheritdoc}
	 */
	public function build(ContainerBuilder $container): void
	{
		parent::build($container);
	}
}
