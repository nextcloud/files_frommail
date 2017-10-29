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


$config = [
	'nextcloud' => 'https://test.artificial-owl.com/',
	'username'  => 'frommail',
	'password'  => 'Ledxc-jRFiR-wBMXD-jyyjt-Y87CZ',
	'debug'     => false
];


// --- do not edit below this line ---

class NextcloudMailCatcher {

	/** @var string */
	private $content;

	/** @var array */
	private $config;


	/**
	 * RemoteMailCatcher constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config) {

		$nextcloud = $config['nextcloud'];
		if (substr($nextcloud, -1) === '/') {
			$config['nextcloud'] = substr($nextcloud, 0, -1);
		}


		$this->config = $config;
		$this->debug('Catching a new mail');
	}


	/**
	 * @param $content
	 *
	 * @return $this
	 */
	public function setContent($content) {
		$this->content = $content;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}


	/**
	 *
	 */
	public function sendToNextcloud() {

		$content = rawurlencode(base64_encode($this->getContent()));

		$curl = $this->generateAuthedCurl(
			$this->config['nextcloud'] . '/index.php/apps/files_frommail/remote/'
		);
		$this->fillCurlWithContent($curl, 'content=' . $content);

		$result = curl_exec($curl);

		$this->debugCurl($curl, $result);
	}


	/**
	 * @param resource $curl
	 * @param string|array $result
	 */
	private function debugCurl($curl, $result) {
		if ($result === false) {
			$this->debug('Mail NOT forwarded: ' . curl_error($curl));

			return;
		}

		try {
			$this->debugCurlResponseCode($curl);
		} catch (Exception $e) {
			$this->debug('Mail NOT forwarded: ' . $e->getMessage());

			return;
		}

		$this->debug('Mail forwarded, result was ' . $result);
	}


	private function debugCurlResponseCode($curl) {

		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($code === 201) {
			return;
		}

		if ($code === 401 || $code === 500) {
			throw new Exception('Unauthorized access');
		}

		if ($code === 404) {
			throw new Exception('404 Not Found');
		}

		if ($code === 503 || $code === 302) {
			throw new Exception('The \'files_frommail\' app does not seems enabled');
		}

		throw new Exception('Request returned code ' . $code);
	}


	/**
	 * @param $url
	 *
	 * @return resource
	 */
	private function generateAuthedCurl($url) {

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt(
			$curl, CURLOPT_USERPWD,
			$this->config['username'] . ':' . $this->config['password']
		);

		$this->debug(
			'Generate curl request to ' . $url . ' with username \'' . $this->config['username'] . '\''
		);

		return $curl;
	}


	/**
	 * @param resource $curl
	 * @param string $put
	 */
	private function fillCurlWithContent(&$curl, $put) {

		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_POSTFIELDS, $put);

		$length = strlen($put);
		curl_setopt(
			$curl, CURLOPT_HTTPHEADER,
			[
				'Content-type: application/x-www-form-urlencoded',
				'OCS-APIRequest: true',
				'Content-Length: ' . $length
			]
		);

		$this->debug('Content-Length: ' . $length . ' (' . round($length / 1024 / 1024, 1) . 'MB)');
	}


	/**
	 * @param string $string
	 */
	private function debug($string) {
		if (!array_key_exists('debug', $this->config) || $this->config['debug'] !== true) {
			return;
		}

		$log = '/tmp/' . basename(__FILE__, '.php') . '.log';
		file_put_contents($log, date('Y-m-d H:i:s') . ' ' . $string . "\n", FILE_APPEND);
	}

}


$content = '';
$fd = fopen('php://stdin', 'r');
while (!feof($fd)) {
	$content .= fread($fd, 1024);
}

$mailCatcher = new NextcloudMailCatcher($config);
$mailCatcher->setContent($content);
$mailCatcher->sendToNextcloud();




