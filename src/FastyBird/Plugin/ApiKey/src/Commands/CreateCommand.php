<?php declare(strict_types = 1);

/**
 * CreateCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Commands;

use Contributte\Translation;
use FastyBird\Plugin\ApiKey\Models;
use FastyBird\Plugin\ApiKey\Types;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function sprintf;

/**
 * API key creation command
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CreateCommand extends Console\Command\Command
{

	use Nette\SmartObject;

	private Translation\PrefixedTranslator $translator;

	private string $translationDomain = 'commands.apiKeyCreate';

	public function __construct(
		private readonly Models\KeysManager $keysManager,
		Translation\Translator $translator,
		string|null $name = null,
	)
	{
		$this->translator = new Translation\PrefixedTranslator($translator, $this->translationDomain);

		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName('fb:api-keys:create')
			->addArgument('name', Input\InputArgument::OPTIONAL, $this->translator->translate('name.title'))
			->addOption('noconfirm', null, Input\InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->setDescription('Create API access key.');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('FB miniserver - create api key');

		$name = $input->hasArgument('name') && $input->getArgument('name') !== ''
			? $input->getArgument('name')
			: $io->ask(
				$this->translator->translate('inputs.name.title'),
			);

		try {
			$createKey = new Utils\ArrayHash();
			$createKey->offsetSet('name', $name);
			$createKey->offsetSet('key', Uuid\Uuid::uuid4());
			$createKey->offsetSet('state', Types\KeyState::get(Types\KeyState::STATE_ACTIVE));

			$key = $this->keysManager->create($createKey);

			$io->text(
				sprintf(
					'<info>%s</info>',
					$this->translator->translate('success', ['name' => $key->getName(), 'key' => $key->getKey()]),
				),
			);

		} catch (Throwable $ex) {
			$io->text(
				sprintf(
					'<error>%s</error>',
					$this->translator->translate('validation.key.wasNotCreated', ['error' => $ex->getMessage()]),
				),
			);
		}

		return 0;
	}

}
