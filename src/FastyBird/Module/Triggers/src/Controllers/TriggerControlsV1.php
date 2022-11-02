<?php declare(strict_types = 1);

/**
 * TriggerControlsV1Controller.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Controllers;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message;
use Ramsey\Uuid;

/**
 * Trigger controls API controller
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class TriggerControlsV1Controller extends BaseV1Controller
{

	use Controllers\Finders\TTriggerFinder;

	/** @var Models\Triggers\ITriggersRepository */
	protected Models\Triggers\ITriggersRepository $triggersRepository;

	/** @var Models\Triggers\Controls\IControlsRepository */
	private Models\Triggers\Controls\IControlsRepository $controlRepository;

	public function __construct(
		Models\Triggers\ITriggersRepository $triggersRepository,
		Models\Triggers\Controls\IControlsRepository $controlRepository
	) {
		$this->triggersRepository = $triggersRepository;
		$this->controlRepository = $controlRepository;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IManualTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		$findQuery = new Queries\FindTriggerControls();
		$findQuery->forTrigger($trigger);

		$controls = $this->controlRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $controls);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IManualTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		if (Uuid\Uuid::isValid($request->getAttribute(Router\Routes::URL_ITEM_ID))) {
			$findQuery = new Queries\FindTriggerControls();
			$findQuery->forTrigger($trigger);
			$findQuery->byId(Uuid\Uuid::fromString($request->getAttribute(Router\Routes::URL_ITEM_ID)));

			// & control
			$control = $this->controlRepository->findOneBy($findQuery);

			if ($control !== null) {
				return $this->buildResponse($request, $response, $control);
			}
		}

		throw new JsonApiExceptions\JsonApiErrorException(
			StatusCodeInterface::STATUS_NOT_FOUND,
			$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
			$this->translator->translate('//triggers-module.base.messages.notFound.message')
		);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IManualTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		// & relation entity name
		$relationEntity = strtolower($request->getAttribute(Router\Routes::RELATION_ENTITY));

		if (Uuid\Uuid::isValid($request->getAttribute(Router\Routes::URL_ITEM_ID))) {
			$findQuery = new Queries\FindTriggerControls();
			$findQuery->forTrigger($trigger);
			$findQuery->byId(Uuid\Uuid::fromString($request->getAttribute(Router\Routes::URL_ITEM_ID)));

			// & control
			$control = $this->controlRepository->findOneBy($findQuery);

			if ($control !== null) {
				if ($relationEntity === Schemas\Triggers\Controls\Control::RELATIONSHIPS_TRIGGER) {
					return $this->buildResponse($request, $response, $control->getTrigger());
				}
			} else {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_NOT_FOUND,
					$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
					$this->translator->translate('//triggers-module.base.messages.notFound.message')
				);
			}
		}

		return parent::readRelationship($request, $response);
	}

}
