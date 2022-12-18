<?php declare(strict_types = 1);

/**
 * TuyaDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function assert;

/**
 * @ORM\Entity
 */
class TuyaDevice extends DevicesEntities\Devices\Device
{

	public const DEVICE_TYPE = 'tuya';

	private TuyaDevice|false|null $gateway = null;

	public function getType(): string
	{
		return self::DEVICE_TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::DEVICE_TYPE;
	}

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA);
	}

	public function getGateway(): TuyaDevice|false
	{
		if ($this->gateway === null) {
			$gateway = $this->parents
				->filter(static fn (DevicesEntities\Devices\Device $row): bool => $row instanceof TuyaDevice)
				->first();

			assert($gateway instanceof TuyaDevice || $gateway === false);

			$this->gateway = $gateway;
		}

		return $this->gateway;
	}

}
