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

namespace OCA\Files_FromMail\Service;


use Exception;
use OCA\Files_FromMail\Exceptions\AddressAlreadyExistException;
use OCA\Files_FromMail\Exceptions\AddressInfoException;
use OCA\Files_FromMail\Exceptions\InvalidAddressException;
use OCA\Files_FromMail\Exceptions\NotAFolderException;
use OCA\Files_FromMail\Exceptions\UnknownAddressException;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Parser;


class MailService {


	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var int */
	private $count = 0;

	/**
	 * MailService constructor.
	 *
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	function __construct(ConfigService $configService, MiscService $miscService) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * parse the mail content.
	 *
	 * will create a local text file containing the headers and the content of the mail for each one of
	 * the 'to' or 'cc' mail address correspond to a mail added using the
	 * "./occ files_frommail:address --add"
	 *
	 * Attachments will also be saved on the cloud in the path:
	 * "Mails sent to yourmail@example.net/From author@example.com/"
	 *
	 * @param string $content
	 * @param string $userId
	 */
	public function parseMail($content, $userId) {
		$mail = new Parser();
		$mail->setText($content);

		$data = $this->parseMailHeaders($mail);
		$data['id'] = date($this->configService->getAppValue(ConfigService::FROMMAIL_FILENAMEID));
		$data['userId'] = $userId;

		$done = [];
		$toAddresses = array_merge($mail->getAddresses('to'), $mail->getAddresses('cc'));
		foreach ($toAddresses as $toAddress) {
			$to = $toAddress['address'];
			if (in_array($to, $done)) {
				continue;
			}

			try {
				$this->generateLocalContentFromMail($mail, $to, $data);
			} catch (Exception $e) {
			}

			$done[] = $to;
		}
	}


	/**
	 * @param Parser $mail
	 * @param string $to
	 * @param array $data
	 */
	private function generateLocalContentFromMail(Parser $mail, $to, $data) {

		$toInfo = $this->getMailAddressInfo($to);
		$text = $data['text'];
		$subject = $data['subject'];
		$from = $data['from'];
		$userId = $data['userId'];
		$id = $data['id'];

		$this->verifyInfoAndPassword($text, $toInfo);

		$this->count = 0;
		$folder = $this->getMailFolder($userId, $to, $from);
		$this->createLocalFile($folder, $id, 'mail-' . $subject . '.txt', $text);
		$this->createLocalFileFromAttachments($id, $folder, $mail->getAttachments());
	}


	/**
	 * @param $content
	 * @param $toInfo
	 *
	 * @throws AddressInfoException
	 */
	private function verifyInfoAndPassword($content, $toInfo) {

		if ($toInfo === null) {
			throw new AddressInfoException('address is not known');
		}

		if (!array_key_exists('password', $toInfo) || $toInfo['password'] === '') {
			return;
		}

		if (strpos($content, ':' . $toInfo['password']) !== false) {
			return;
		}

		throw new AddressInfoException('password is set but not used in mail');
	}


	/**
	 * @param string $userId
	 * @param string $to
	 * @param string $from
	 *
	 * @return Folder
	 * @throws NotAFolderException
	 */
	private function getMailFolder($userId, $to, $from) {

		$node = \OC::$server->getUserFolder($userId);
		$folderPath = 'Mails sent to ' . $to . '/From ' . $from . '/';

		if (!$node->nodeExists($folderPath)) {
			$node->newFolder($folderPath);
		}

		$folder = $node->get($folderPath);
		if ($folder->getType() !== FileInfo::TYPE_FOLDER) {
			throw new NotAFolderException($folderPath . ' is not a folder');
		}

		/** @var Folder $folder */
		return $folder;
	}


	/**
	 * @param Parser $mail
	 *
	 * @return array
	 */
	private function parseMailHeaders(Parser $mail) {
		$from = $mail->getAddresses('from')[0]['address'];
		$subject = $mail->getHeader('subject');
		$text = $mail->getHeadersRaw() . $mail->getMessageBody('text');

		//TODO: check that data are enough
		return [
			'from'    => $from,
			'subject' => $subject,
			'text'    => $text
		];
	}


	/**
	 * @param string $id
	 * @param Folder $folder
	 * @param Attachment[] $attachments
	 */
	private function createLocalFileFromAttachments($id, $folder, $attachments) {
		foreach ($attachments as $attachment) {
			$this->createLocalFile(
				$folder, $id, 'attachment-' . $attachment->getFilename(),
				$attachment->getContent()
			);
		}

	}


	/**
	 * @param Folder $folder
	 * @param string $id
	 * @param string $filename
	 * @param string $content
	 */
	private function createLocalFile($folder, $id, $filename, $content) {
		$new = $folder->newFile($id . '-' . $this->count . '_' . $filename);
		$new->putContent($content);

		$this->count++;
	}


	/**
	 * @param $address
	 * @param $password
	 *
	 * @throws UnknownAddressException
	 */
	public function setMailPassword($address, $password) {
		if (!$this->mailAddressExist($address)) {
			throw new UnknownAddressException('address is not known');
		}
		if ($password === null) {
			$password = '';
		}

		$addresses = $this->getMailAddresses();
		$new = [];
		foreach ($addresses as $entry) {
			if ($entry['address'] === $address) {
				$entry['password'] = $password;
			}
			$new[] = $entry;
		}

		$this->saveMailAddresses($new);
	}


	/**
	 * @param $address
	 *
	 * @throws UnknownAddressException
	 */
	public function removeMailAddress($address) {
		$addresses = $this->getMailAddresses();
		if (!$this->mailAddressExist($address)) {
			throw new UnknownAddressException('address is not known');
		}

		$new = [];
		foreach ($addresses as $entry) {
			if ($entry['address'] !== $address) {
				$new[] = $entry;
			}
		}

		$this->saveMailAddresses($new);
	}


	/**
	 * @param $address
	 *
	 * @throws AddressAlreadyExistException
	 */
	public function addMailAddress($address) {
		$this->hasToBeAValidMailAddress($address);
		if ($this->mailAddressExist($address)) {
			throw new AddressAlreadyExistException('address already exist');
		}

		$addresses = $this->getMailAddresses();
		array_push($addresses, ['address' => $address]);
		$this->saveMailAddresses($addresses);
	}


	/**
	 * @param $address
	 *
	 * @return bool
	 */
	private function mailAddressExist($address) {
		return ($this->getMailAddressInfo($address) !== null);
	}


	/**
	 * @param $address
	 *
	 * @return array|null
	 */
	private function getMailAddressInfo($address) {
		$addresses = $this->getMailAddresses();
		foreach ($addresses as $entry) {
			if ($entry['address'] === $address) {
				return $entry;
			}
		}

		return null;
	}


	/**
	 * @param $address
	 *
	 * @throws InvalidAddressException
	 */
	private function hasToBeAValidMailAddress($address) {
		if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
			return;
		}

		throw new InvalidAddressException('this mail address is not valid');
	}


	/**
	 * @return array
	 */
	public function getMailAddresses() {
		$curr = json_decode($this->configService->getAppValue(ConfigService::FROMMAIL_ADDRESSES), true);
		if ($curr === null) {
			return [];
		}

		return $curr;
	}

	/**
	 * @param $addresses
	 */
	private function saveMailAddresses($addresses) {
		$this->configService->setAppValue(ConfigService::FROMMAIL_ADDRESSES, json_encode($addresses));
	}


}
