<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Connectors
 * @since          0.60.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Module\Devices\Connectors;

/**
 * Devices connector interface
 *
 * @package        FastyBird:Devices!
 * @subpackage     Connectors
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Connector
{

	public function execute(): void;

	public function terminate(): void;

	public function hasUnfinishedTasks(): bool;

}
