<?php declare(strict_types = 1);

/**
 * LocalApiFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          0.13.0
 *
 * @date           05.09.22
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Psr\Log;
use React\EventLoop;

/**
 * Local device API factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalApiFactory
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(
		string $identifier,
		string|null $gateway,
		string $localKey,
		string $ipAddress,
		Types\DeviceProtocolVersion $protocolVersion,
	): LocalApi
	{
		return new LocalApi(
			$identifier,
			$gateway,
			$localKey,
			$ipAddress,
			$protocolVersion,
			$this->schemaValidator,
			$this->dateTimeFactory,
			$this->eventLoop,
			$this->logger,
		);
	}

}
