<?php declare(strict_types = 1);

/**
 * SyncDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Nette;
use stdClass;

/**
 * Synchronise third-party devices with NS Panel response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SyncDevices implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly int $error,
		private readonly SyncDevicesEvent $data,
		private readonly string $message,
	)
	{
	}

	public function getError(): int
	{
		return $this->error;
	}

	public function getData(): SyncDevicesEvent
	{
		return $this->data;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'error' => $this->getError(),
			'data' => $this->getData(),
			'message' => $this->getMessage(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->error = $this->getError();
		$json->data = $this->getData()->toJson();
		$json->message = $this->getMessage();

		return $json;
	}

}
