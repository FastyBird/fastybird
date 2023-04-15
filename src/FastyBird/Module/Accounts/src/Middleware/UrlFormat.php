<?php declare(strict_types = 1);

/**
 * UrlFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           21.06.20
 */

namespace FastyBird\Module\Accounts\Middleware;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Metadata;
use FastyBird\Module\Accounts\Security;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\SlimRouter\Http;
use Nette\Localization;
use Nette\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function str_replace;

/**
 * Access token check middleware
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class UrlFormat implements MiddlewareInterface
{

	public function __construct(
		private readonly bool $usePrefix,
		private readonly Security\User $user,
		private readonly Localization\Translator $translator,
	)
	{
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		if (
			$this->user->isLoggedIn()
			&& (
				Utils\Strings::startsWith($request->getUri()
					->getPath(), ($this->usePrefix ? '/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX : '') . '/v1/session')
				|| Utils\Strings::startsWith($request->getUri()
					->getPath(), ($this->usePrefix ? '/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX : '') . '/v1/me')
			)
		) {
			if ($this->user->getAccount() === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_BAD_REQUEST,
					$this->translator->translate('//accounts-module.base.messages.failed.heading'),
					$this->translator->translate('//accounts-module.base.messages.failed.message'),
				);
			}

			$body = $response->getBody();
			$body->rewind();

			$content = $body->getContents();
			$content = str_replace('\/v1\/emails', '\/v1\/me\/emails', $content);
			$content = str_replace('\/v1\/identities', '\/v1\/me\/identities', $content);
			$content = str_replace('\/v1\/accounts\/' . $this->user->getAccount()->getPlainId(), '\/v1\/me', $content);
			$content = str_replace(
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/emails',
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/emails',
				$content,
			);
			$content = str_replace(
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/identities',
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me\/identities',
				$content,
			);
			$content = str_replace(
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/accounts\/' . $this->user->getAccount()->getPlainId(),
				'\/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '\/v1\/me',
				$content,
			);

			$response = $response->withBody(Http\Stream::fromBodyString($content));
		}

		return $response;
	}

}
