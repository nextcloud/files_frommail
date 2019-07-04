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

namespace OCA\Files_FromMail\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\Files_FromMail\Exceptions\AddressAlreadyExistException;
use OCA\Files_FromMail\Exceptions\FakeException;
use OCA\Files_FromMail\Exceptions\InvalidAddressException;
use OCA\Files_FromMail\Exceptions\MissingArgumentException;
use OCA\Files_FromMail\Exceptions\UnknownAddressException;
use OCA\Files_FromMail\Service\MailService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Addresses extends Base {


	/** @var MailService */
	private $mailService;

	/**
	 * Addresses constructor.
	 *
	 * @param MailService $mailService
	 */
	public function __construct(MailService $mailService) {
		parent::__construct();

		$this->mailService = $mailService;
	}


	/**
	 * ./occ files_frommail:address to manage the mail address to get caught by the app.
	 *
	 * ./occ files_frommail:address --list
	 * ./occ files_frommail:address --add mail_address
	 * ./occ files_frommail:address --remove mail_address
	 * ./occ files_frommail:address --password mail_address password
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('files_frommail:address')
			 ->setDescription('manage the linked groups')
			 ->addOption('add', 'a', InputOption::VALUE_NONE, 'add a new mail address')
			 ->addOption('list', 'l', InputOption::VALUE_NONE, 'list all mail addresses')
			 ->addOption('remove', 'r', InputOption::VALUE_NONE, 'remove a mail address')
			 ->addOption(
				 'password', 'p', InputOption::VALUE_NONE, 'add a password to protect a mail address'
			 )
			 ->addArgument('address', InputArgument::OPTIONAL, 'mail address')
			 ->addArgument('password', InputArgument::OPTIONAL, 'password');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		try {
			$this->listMailAddresses($input, $output);
			$this->addMailAddress($input);
			$this->removeMailAddress($input);
			$this->setMailAddressPassword($input, $output);

			$output->writeln('please specify an action or use --help');
		} catch (FakeException $e) {
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 */
	private function listMailAddresses(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('list') !== true) {
			return;
		}

		$addresses = $this->mailService->getMailAddresses();

		if (sizeof($addresses) === 0) {
			$output->writeln('no mail address');
			throw new FakeException();
		}

		foreach ($addresses as $entry) {
			$output->writeln($this->formatMailAddress($entry));
		}

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 *
	 * @throws FakeException
	 * @throws MissingArgumentException
	 * @throws AddressAlreadyExistException
	 * @throws InvalidAddressException
	 */
	private function addMailAddress(InputInterface $input) {
		if ($input->getOption('add') !== true) {
			return;
		}

		$mail = $this->checkMailAddress($input);
		$this->mailService->addMailAddress($mail);

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 *
	 * @throws FakeException
	 * @throws MissingArgumentException
	 * @throws UnknownAddressException
	 */
	private function removeMailAddress(InputInterface $input) {
		if ($input->getOption('remove') !== true) {
			return;
		}

		$mail = $this->checkMailAddress($input);
		$this->mailService->removeMailAddress($mail);

		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws FakeException
	 * @throws MissingArgumentException
	 * @throws UnknownAddressException
	 */
	private function setMailAddressPassword(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('password') !== true) {
			return;
		}

		$mail = $this->checkMailAddress($input);
		$password = $input->getArgument('password');
		$this->mailService->setMailPassword($mail, $password);

		if ($password === null) {
			$output->writeln('Password for ' . $mail . ' is now UNSET');
			throw new FakeException();
		}

		$output->writeln('Password for ' . $mail . ' is now SET to ' . $password);
		throw new FakeException();
	}


	/**
	 * @param InputInterface $input
	 *
	 * @return string|string[]|null
	 * @throws MissingArgumentException
	 */
	private function checkMailAddress(InputInterface $input) {
		$mail = $input->getArgument('address');
		if ($mail === null) {
			throw new MissingArgumentException('missing email address');
		}

		return $mail;

	}


	/**
	 * @param array $entry
	 *
	 * @return string
	 */
	private function formatMailAddress($entry) {
		$line = '- ' . $entry['address'];
		if (array_key_exists('password', $entry) && $entry['password'] !== '') {
			$line .= ' [:' . $entry['password'] . ']';
		}

		return $line;
	}

}

