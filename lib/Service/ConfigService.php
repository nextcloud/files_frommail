<?php declare(strict_types=1);


/**
 * Files_FromMail - Recover your email attachments from your cloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Files_FromMail\Service;

use OCP\IConfig;
use OCP\IRequest;


/**
 * Class ConfigService
 *
 * @package OCA\Files_FromMail\Service
 */
class ConfigService {

	const FROMMAIL_ADDRESSES = 'frommail_addresses';
	const FROMMAIL_FILENAMEID = 'filename_id';

	private $defaults = [
		self::FROMMAIL_ADDRESSES  => '',
		self::FROMMAIL_FILENAMEID => 'Y-m-d H:i:s',
	];


	/** @var string */
	private $appName;

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var IRequest */
	private $request;

	/** @var MiscService */
	private $miscService;


	/**
	 * ConfigService constructor.
	 *
	 * @param string $appName
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param string $userId
	 * @param MiscService $miscService
	 */
	public function __construct(
		$appName, IConfig $config, IRequest $request, $userId, MiscService $miscService
	) {
		$this->appName = $appName;
		$this->config = $config;
		$this->request = $request;
		$this->userId = $userId;
		$this->miscService = $miscService;
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string {
		$defaultValue = null;

		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getAppValue($this->appName, $key, $defaultValue);
	}


	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value): void {
		$this->config->setAppValue($this->appName, $key, $value);
	}


	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue(string $key): string {
		return $this->config->deleteAppValue($this->appName, $key);
	}

}

