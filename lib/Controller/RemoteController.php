<?php
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

use OCA\Files_FromMail\AppInfo\Application;
use OCA\Files_FromMail\Service\MailService;
use OCA\Files_FromMail\Service\MiscService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class RemoteController extends Controller {

	/** @var string */
	private $userId;

	/** @var MailService */
	private $mailService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param string $userId
	 * @param MailService $mailService
	 * @param MiscService $miscService
	 */
	function __construct(IRequest $request, $userId, MailService $mailService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->userId = $userId;

		$this->mailService = $mailService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param $content
	 *
	 * @return DataResponse
	 */
	public function getContent($content) {
		$content = base64_decode(rawurldecode($content));
		$this->mailService->parseMail($content, $this->userId);

		return new DataResponse(['ok'], Http::STATUS_CREATED);
	}


}