<?php declare(strict_types = 1);

/**
 * Gen1HttpApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use Psr\Log;
use React\EventLoop;

/**
 * Generation 1 device http API factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1HttpApiFactory
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly EntityFactory $entityFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function create(): Gen1HttpApi
	{
		return new Gen1HttpApi(
			$this->entityFactory,
			$this->schemaValidator,
			$this->eventLoop,
			$this->logger,
		);
	}

}
