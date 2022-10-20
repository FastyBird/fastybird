<?php declare(strict_types = 1);

/**
 * Flow.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          0.1.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use React\Promise;

/**
 * Decorates flows with data required for the {@see Client} class.
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Flow implements Mqtt\Flow
{

	public function __construct(
		private readonly Mqtt\Flow $decorated,
		private readonly Promise\Deferred $deferred,
		private Mqtt\Packet|null $packet = null,
		private readonly bool $isSilent = false,
	)
	{
	}

	public function getCode(): string
	{
		return $this->decorated->getCode();
	}

	public function start(): Mqtt\Packet|null
	{
		$this->packet = $this->decorated->start();

		return $this->packet;
	}

	public function accept(Mqtt\Packet $packet): bool
	{
		return $this->decorated->accept($packet);
	}

	public function next(Mqtt\Packet $packet): Mqtt\Packet|null
	{
		$this->packet = $this->decorated->next($packet);

		return $this->packet;
	}

	public function isFinished(): bool
	{
		return $this->decorated->isFinished();
	}

	public function isSuccess(): bool
	{
		return $this->decorated->isSuccess();
	}

	public function getResult()
	{
		return $this->decorated->getResult();
	}

	public function getErrorMessage(): string
	{
		return $this->decorated->getErrorMessage();
	}

	/**
	 * Returns the associated deferred.
	 */
	public function getDeferred(): Promise\Deferred
	{
		return $this->deferred;
	}

	/**
	 * Returns the current packet.
	 */
	public function getPacket(): Mqtt\Packet|null
	{
		return $this->packet;
	}

	/**
	 * Indicates if the flow should emit events.
	 */
	public function isSilent(): bool
	{
		return $this->isSilent;
	}

}
