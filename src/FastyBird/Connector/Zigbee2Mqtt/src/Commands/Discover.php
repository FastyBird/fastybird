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
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Localization;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use TypeError;
use ValueError;
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
		private readonly DateTimeFactory\Clock $clock,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 * @throws ToolsExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return Console\Command\Command::FAILURE;
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.title'));

		$io->note((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.questions.continue'),
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

			$findConnectorQuery = new Queries\Configuration\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$connector = $this->connectorsConfigurationRepository->findOneBy(
				$findConnectorQuery,
				Documents\Connectors\Connector::class,
			);

			if ($connector === null) {
				$io->warning(
					(string) $this->translator->translate(
						'//zigbee2mqtt-connector.cmd.discover.messages.connector.notFound',
					),
				);

				return Console\Command\Command::FAILURE;
			}
		} else {
			$connectors = [];

			$findConnectorsQuery = new Queries\Configuration\FindConnectors();

			$systemConnectors = $this->connectorsConfigurationRepository->findAllBy(
				$findConnectorsQuery,
				Documents\Connectors\Connector::class,
			);
			usort(
				$systemConnectors,
				static fn (Documents\Connectors\Connector $a, Documents\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier(),
			);

			foreach ($systemConnectors as $connector) {
				$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
					. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
			}

			if (count($connectors) === 0) {
				$io->warning(
					(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.noConnectors'),
				);

				return Console\Command\Command::FAILURE;
			}

			if (count($connectors) === 1) {
				$connectorIdentifier = array_key_first($connectors);

				$findConnectorQuery = new Queries\Configuration\FindConnectors();
				$findConnectorQuery->byIdentifier($connectorIdentifier);

				$connector = $this->connectorsConfigurationRepository->findOneBy(
					$findConnectorQuery,
					Documents\Connectors\Connector::class,
				);

				if ($connector === null) {
					$io->warning(
						(string) $this->translator->translate(
							'//zigbee2mqtt-connector.cmd.discover.messages.connector.notFound',
						),
					);

					return Console\Command\Command::FAILURE;
				}

				if ($input->getOption('no-interaction') === false) {
					$question = new Console\Question\ConfirmationQuestion(
						(string) $this->translator->translate(
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
					(string) $this->translator->translate(
						'//zigbee2mqtt-connector.cmd.discover.questions.select.connector',
					),
					array_values($connectors),
				);
				$question->setErrorMessage(
					(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.base.messages.answerNotValid'),
				);
				$question->setValidator(
					function (string|int|null $answer) use ($connectors): Documents\Connectors\Connector {
						if ($answer === null) {
							throw new Exceptions\Runtime(
								sprintf(
									(string) $this->translator->translate(
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
							$findConnectorQuery = new Queries\Configuration\FindConnectors();
							$findConnectorQuery->byIdentifier($identifier);

							$connector = $this->connectorsConfigurationRepository->findOneBy(
								$findConnectorQuery,
								Documents\Connectors\Connector::class,
							);

							if ($connector !== null) {
								return $connector;
							}
						}

						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//zigbee2mqtt-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					},
				);

				$connector = $io->askQuestion($question);
				assert($connector instanceof Documents\Connectors\Connector);
			}
		}

		if (!$connector->isEnabled()) {
			$io->warning(
				(string) $this->translator->translate(
					'//zigbee2mqtt-connector.cmd.discover.messages.connector.disabled',
				),
			);

			return Console\Command\Command::SUCCESS;
		}

		$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.starting'));

		$this->executedTime = $this->clock->getNow();

		$serviceCmd = $symfonyApp->find(DevicesCommands\Connector::NAME);

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesTypes\ConnectorMode::DISCOVER->value,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $output);

		$io->newLine(2);

		$io->info((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.stopping'));

		if ($result !== Console\Command\Command::SUCCESS) {
			$io->error((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.execute.messages.error'));

			return Console\Command\Command::FAILURE;
		}

		$this->showResults($io, $output, $connector);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function showResults(
		Style\SymfonyStyle $io,
		Output\OutputInterface $output,
		Documents\Connectors\Connector $connector,
	): void
	{
		$table = new Console\Helper\Table($output);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.id'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.name'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.model'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.manufacturer'),
			(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.data.bridge'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new Queries\Configuration\FindBridgeDevices();
		$findDevicesQuery->forConnector($connector);

		$bridges = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Bridge::class,
		);

		foreach ($bridges as $bridge) {
			$findDevicesQuery = new Queries\Configuration\FindSubDevices();
			$findDevicesQuery->forConnector($connector);
			$findDevicesQuery->forParent($bridge);

			$subDevices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\SubDevice::class,
			);

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
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.foundDevices'),
				$foundDevices,
			));

			$table->render();

			$io->newLine();

		} else {
			$io->info(
				(string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.noDevicesFound'),
			);
		}

		$io->success((string) $this->translator->translate('//zigbee2mqtt-connector.cmd.discover.messages.success'));
	}

}
