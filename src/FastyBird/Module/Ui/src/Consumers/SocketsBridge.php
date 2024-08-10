<?php declare(strict_types = 1);

/**
 * SocketsBridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           17.4.23
 */

namespace FastyBird\Module\Ui\Consumers;

use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumer;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Ui;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Throwable;
use function in_array;

/**
 * Exchange to sockets bridge consumer
 *
 * @package        FastyBird:UiModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SocketsBridge implements ExchangeConsumer\Consumer
{

	public function __construct(
		private Ui\Logger $logger,
		private WebSockets\Router\LinkGenerator $linkGenerator,
		private WebSocketsWAMP\Topics\IStorage $topicsStorage,
	)
	{
	}

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		MetadataDocuments\Document|null $document,
	): void
	{
		if (!in_array($routingKey, Ui\Constants::MESSAGE_BUS_ROUTING_KEYS, true)) {
			return;
		}

		$result = $this->sendMessage(
			[
				'routing_key' => $routingKey,
				'source' => $source->value,
				'data' => $document?->toArray(),
			],
		);

		if ($result) {
			$this->logger->debug(
				'Successfully published message',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'message' => [
						'routing_key' => $routingKey,
						'source' => $source->value,
						'data' => $document?->toArray(),
					],
				],
			);

		} else {
			$this->logger->error(
				'Message could not be published to exchange',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'message' => [
						'routing_key' => $routingKey,
						'source' => $source->value,
						'data' => $document?->toArray(),
					],
				],
			);
		}

		$this->logger->debug(
			'Received message from exchange was pushed to WS clients',
			[
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'type' => 'sockets-consumer',
				'message' => [
					'routing_key' => $routingKey,
					'source' => $source->value,
					'entity' => $document?->toArray(),
				],
			],
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function sendMessage(array $data): bool
	{
		try {
			$link = $this->linkGenerator->link('DevicesModule:Exchange:');

			if ($this->topicsStorage->hasTopic($link)) {
				$topic = $this->topicsStorage->getTopic($link);

				$this->logger->debug(
					'Broadcasting message to topic',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'sockets-consumer',
						'link' => $link,
					],
				);

				$topic->broadcast(Utils\Json::encode($data));
			}

			return true;
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Data could not be converted to message',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Data could not be broadcasts to clients',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'sockets-consumer',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);
		}

		return false;
	}

}
