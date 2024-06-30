<?php declare(strict_types = 1);

/**
 * UserPanel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           19.06.24
 */

namespace FastyBird\Module\Accounts\Utilities;

use FastyBird\Module\Accounts\Security;
use Nette\Utils;
use Tracy;
use const DIRECTORY_SEPARATOR;

/**
 * User panel for Debugger Bar
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class UserPanel implements Tracy\IBarPanel
{

	public function __construct(private readonly Security\User $user)
	{
	}

	/**
	 * Renders tab
	 */
	public function getTab(): string
	{
		return Utils\Helpers::capture(function (): void {
			// phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
			$status = $this->user->isLoggedIn();

			require __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UserPanel.tab.phtml';
		});
	}

	/**
	 * Renders panel
	 */
	public function getPanel(): string
	{
		return Utils\Helpers::capture(function (): void {
			// phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
			$user = $this->user;

			require __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'UserPanel.panel.phtml';
		});
	}

}
