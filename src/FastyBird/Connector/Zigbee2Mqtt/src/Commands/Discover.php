<?php declare(strict_types = 1);

/**
 * Discover.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Commands;

use DateTimeInterface;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\Localization;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use function array_key_exists;
use function array_key_first;
use function array_search;
use function array_values;
use function assert;
use function count;
use function is_string;
use function sprintf;
use function usort;

/**
 * Connector devices discovery command
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Discover extends Console\Command\Command
{

	public const NAME = 'fb:zigbee2mqtt-connector:discover';

	private DateTimeInterface|null $executedTime = null;

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly Localization\Translator $translator,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Zigbee2MQTT connector discovery')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_OPTIONAL,
						'Connector ID or identifier',
						true,
					),
					new Input\InputOption(
						'device',
						'd',
						Input\InputOption::VALUE_OPTIONAL,
						'Device ID or identifier',
						true,
					),
				]),
			);
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return Console\Command\Command::FAILURE;
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.title'));

		$io->note($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		if (
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new Queries\Entities\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\Zigbee2MqttConnector::class,
			);

			if ($connector === null) {
				$io->warning(
					$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.connector.notFound'),
				);

				return Console\Command\Command::FAILURE;
			}
		} else {
			$connectors = [];

			$findConnectorsQuery = new Queries\Entities\FindConnectors();

			$systemConnectors = $this->connectorsRepository->findAllBy(
				$findConnectorsQuery,
				Entities\Zigbee2MqttConnector::class,
			);
			usort(
				$systemConnectors,
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (Entities\Zigbee2MqttConnector $a, Entities\Zigbee2MqttConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
			);

			foreach ($systemConnectors as $connector) {
				$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
					. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
			}

			if (count($connectors) === 0) {
				$io->warning($this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.noConnectors'));

				return Console\Command\Command::FAILURE;
			}

			if (count($connectors) === 1) {
				$connectorIdentifier = array_key_first($connectors);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($connectorIdentifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Zigbee2MqttConnector::class,
				);

				if ($connector === null) {
					$io->warning(
						$this->translator->translate(
							'//zigbee2mqtt-connector.cmd.discover.messages.connector.notFound',
						),
					);

					return Console\Command\Command::FAILURE;
				}

				if ($input->getOption('no-interaction') === false) {
					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//zigbee2mqtt-connector.cmd.discover.questions.execute',
							['connector' => $connector->getName() ?? $connector->getIdentifier()],
						),
						false,
					);

					if ($io->askQuestion($question) === false) {
						return Console\Command\Command::SUCCESS;
					}
				}
			} else {
				$question = new Console\Question\ChoiceQuestion(
					$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.questions.select.connector'),
					array_values($connectors),
				);
				$question->setErrorMessage(
					$this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
				);
				$question->setValidator(
					function (string|int|null $answer) use ($connectors): Entities\Zigbee2MqttConnector {
						if ($answer === null) {
							throw new Exceptions\Runtime(
								sprintf(
									$this->translator->translate(
										'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
									),
									$answer,
								),
							);
						}

						if (array_key_exists($answer, array_values($connectors))) {
							$answer = array_values($connectors)[$answer];
						}

						$identifier = array_search($answer, $connectors, true);

						if ($identifier !== false) {
							$findConnectorQuery = new Queries\Entities\FindConnectors();
							$findConnectorQuery->byIdentifier($identifier);

							$connector = $this->connectorsRepository->findOneBy(
								$findConnectorQuery,
								Entities\Zigbee2MqttConnector::class,
							);

							if ($connector !== null) {
								return $connector;
							}
						}

						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate(
									'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					},
				);

				$connector = $io->askQuestion($question);
				assert($connector instanceof Entities\Zigbee2MqttConnector);
			}
		}

		if (!$connector->isEnabled()) {
			$io->warning(
				$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.connector.disabled'),
			);

			return Console\Command\Command::SUCCESS;
		}

		$this->executedTime = $this->dateTimeFactory->getNow();

		$serviceCmd = $symfonyApp->find(DevicesCommands\Connector::NAME);

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesCommands\Connector::MODE_DISCOVER,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $output);

		if ($result !== Console\Command\Command::SUCCESS) {
			$io->error($this->translator->translate('//zigbee2mqtt-connector.cmd.execute.messages.error'));

			return Console\Command\Command::FAILURE;
		}

		$this->showResults($io, $output, $connector);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function showResults(
		Style\SymfonyStyle $io,
		Output\OutputInterface $output,
		Entities\Zigbee2MqttConnector $connector,
	): void
	{
		$io->newLine();

		$table = new Console\Helper\Table($output);
		$table->setHeaders([
			'#',
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.id'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.name'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.model'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.bridge'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridges = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Bridge::class);

		foreach ($bridges as $bridge) {
			$findDevicesQuery = new Queries\Entities\FindSubDevices();
			$findDevicesQuery->forConnector($connector);
			$findDevicesQuery->forParent($bridge);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

			foreach ($devices as $device) {
				$createdAt = $device->getCreatedAt();

				if (
					$createdAt !== null
					&& $this->executedTime !== null
					&& $createdAt->getTimestamp() > $this->executedTime->getTimestamp()
				) {
					$foundDevices++;

					$table->addRow([
						$foundDevices,
						$device->getId()->toString(),
						$device->getName() ?? $device->getIdentifier(),
						$device->getHardwareType(),
						$bridge->getName() ?? $bridge->getIdentifier(),
					]);
				}
			}
		}

		if ($foundDevices > 0) {
			$io->newLine();

			$io->info(sprintf(
				$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.foundDevices'),
				$foundDevices,
			));

			$table->render();

			$io->newLine();

		} else {
			$io->info($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.noDevicesFound'));
		}

		$io->success($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.success'));
	}

}
