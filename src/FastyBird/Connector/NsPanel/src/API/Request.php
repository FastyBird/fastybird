<?php declare(strict_types = 1);

/**
 * LanApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\API;

use FastyBird\Connector\NsPanel\Exceptions;
use RuntimeException;
use Sunrise\Http;
use Throwable;

/**
 * HTTP request
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Request extends Http\Message\Request
{

	/**
	 * @param array<string, string|array<string>>|null $headers
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $method,
		string $uri,
		array|null $headers = null,
		string|null $body = null,
	)
	{
		try {
			$stream = null;

			if ($body !== null) {
				$stream = new Http\Message\Stream\PhpTempStream();

				$stream->write($body);
				$stream->rewind();
			}

			parent::__construct($method, $uri, $headers, $stream);
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidArgument('Request could not be created', $ex->getCode(), $ex);
		}
	}

	public function getContent(): string|null
	{
		try {
			$content = $this->getBody()->getContents();

			$this->getBody()->rewind();

			return $content;
		} catch (RuntimeException) {
			return null;
		}
	}

}