<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\HomeKit\Router;

use FastyBird\Connector\HomeKit\Controllers;
use IPub\SlimRouter\Routing;

/**
 * Connector router configuration
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Router extends Routing\Router
{

	public function __construct(
		Controllers\PairingController $pairingController,
		Controllers\AccessoriesController $accessoriesController,
		Controllers\CharacteristicsController $characteristicsController,
		Controllers\DiagnosticsController $diagnosticsController,
	)
	{
		parent::__construct();

		// Pairing process requests
		$this->post('/pair-setup', [$pairingController, 'setup']);
		$this->post('/pair-verify', [$pairingController, 'verify']);
		$this->post('/pairings', [$pairingController, 'pairings']);

		$this->group(
			'/accessories',
			static function (Routing\RouteCollector $group) use ($accessoriesController): void {
				$group->get('', [$accessoriesController, 'index']);
			},
		);
		$this->post('/resource', [$accessoriesController, 'resource']);
		$this->post('/identify', [$accessoriesController, 'identify']);

		$this->group(
			'/characteristics',
			static function (Routing\RouteCollector $group) use ($characteristicsController): void {
				$group->get('', [$characteristicsController, 'index']);
				$group->put('', [$characteristicsController, 'update']);
			},
		);
		$this->put('/prepare', [$characteristicsController, 'prepare']);

		$this->group(
			'/diagnostics',
			static function (Routing\RouteCollector $group) use ($diagnosticsController): void {
				$group->get('', [$diagnosticsController, 'index']);
			},
		);
	}

}
