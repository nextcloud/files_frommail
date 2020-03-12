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


namespace OCA\Files_FromMail\Controller;

use Exception;
use OCA\Files_FromMail\AppInfo\Application;
use OCA\Files_FromMail\Service\MailService;
use OCA\Files_FromMail\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class NavigationController
 *
 * @package OCA\Files_FromMail\Controller
 */
class NavigationController extends Controller {


	/** @var MailService */
	private $mailService;

	/** @var MiscService */
	private $miscService;


	/**
	 * RemoteController constructor.
	 *
	 * @param IRequest $request
	 * @param string $userId
	 * @param MailService $mailService
	 * @param MiscService $miscService
	 */
	function __construct(IRequest $request, MailService $mailService, MiscService $miscService) {
		parent::__construct(Application::APP_NAME, $request);

		$this->mailService = $mailService;
		$this->miscService = $miscService;
	}


	/**
	 * @return DataResponse
	 */
	public function getMailbox(): DataResponse {
		try {
			$mailbox = $this->mailService->getMailAddresses();

			return new DataResponse($mailbox, Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


	/**
	 * @param string $address
	 * @param string $password
	 *
	 * @return DataResponse
	 */
	public function newMailbox(string $address, string $password): DataResponse {
		try {
			$this->mailService->addMailAddress($address, $password);

			return new DataResponse(['ok'], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


	/**
	 * @param string $address
	 *
	 * @return DataResponse
	 */
	public function deleteMailbox(string $address): DataResponse {
		try {
			$this->mailService->removeMailAddress($address);

			return new DataResponse(['ok'], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}


}

