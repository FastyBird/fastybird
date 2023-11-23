<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use DateTimeInterface;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use React\Promise;

/**
 * Device service interface
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Driver
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function disconnect(): Promise\PromiseInterface;

	public function isConnected(): bool;

	public function isConnecting(): bool;

	public function getLastConnectAttempt(): DateTimeInterface|null;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function process(): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function writeState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function notifyState(
		MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue,
	): Promise\PromiseInterface;

}
