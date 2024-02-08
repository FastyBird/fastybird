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
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
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
		private readonly Helpers\Devices\SubDevice $subDeviceHelper,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
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

			$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
			$findConnectorQuery->byType(Entities\Zigbee2MqttConnector::TYPE);

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

			if ($connector === null) {
				$io->warning(
					$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.connector.notFound'),
				);

				return Console\Command\Command::FAILURE;
			}
		} else {
			$connectors = [];

			$findConnectorsQuery = new DevicesQueries\Configuration\FindConnectors();
			$findConnectorsQuery->byType(Entities\Zigbee2MqttConnector::TYPE);

			$systemConnectors = $this->connectorsConfigurationRepository->findAllBy($findConnectorsQuery);
			usort(
				$systemConnectors,
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (MetadataDocuments\DevicesModule\Connector $a, MetadataDocuments\DevicesModule\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
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

				$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
				$findConnectorQuery->byIdentifier($connectorIdentifier);
				$findConnectorQuery->byType(Entities\Zigbee2MqttConnector::TYPE);

				$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

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
					function (string|int|null $answer) use ($connectors): MetadataDocuments\DevicesModule\Connector {
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
							$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
							$findConnectorQuery->byIdentifier($identifier);
							$findConnectorQuery->byType(Entities\Zigbee2MqttConnector::TYPE);

							$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

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
				assert($connector instanceof MetadataDocuments\DevicesModule\Connector);
			}
		}

		if (!$connector->isEnabled()) {
			$io->warning(
				$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.connector.disabled'),
			);

			return Console\Command\Command::SUCCESS;
		}

		$io->info($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.starting'));

		$this->executedTime = $this->dateTimeFactory->getNow();

		$serviceCmd = $symfonyApp->find(DevicesCommands\Connector::NAME);

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesTypes\ConnectorMode::DISCOVER->value,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $output);

		$io->newLine(2);

		$io->info($this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.stopping'));

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
		MetadataDocuments\DevicesModule\Connector $connector,
	): void
	{
		$table = new Console\Helper\Table($output);
		$table->setHeaders([
			'#',
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.id'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.name'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.model'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.manufacturer'),
			$this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.bridge'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);
		$findDevicesQuery->byType(Entities\Devices\Bridge::TYPE);

		$bridges = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		foreach ($bridges as $bridge) {
			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($connector);
			$findDevicesQuery->forParent($bridge);
			$findDevicesQuery->byType(Entities\Devices\SubDevice::TYPE);

			$subDevices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			foreach ($subDevices as $subDevice) {
				$createdAt = $subDevice->getCreatedAt();

				if (
					$createdAt !== null
					&& $this->executedTime !== null
					&& $createdAt->getTimestamp() > $this->executedTime->getTimestamp()
				) {
					$foundDevices++;

					$table->addRow([
						$foundDevices,
						$subDevice->getId()->toString(),
						$subDevice->getName() ?? $subDevice->getIdentifier(),
						$this->subDeviceHelper->getHardwareModel($subDevice),
						$this->subDeviceHelper->getHardwareManufacturer($subDevice),
						$bridge->getName() ?? $bridge->getIdentifier(),
					]);
				}
			}
		}

		if ($foundDevices > 0) {
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
