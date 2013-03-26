<?php

class CM_Stream_Adapter_Message_SocketRedis extends CM_Stream_Adapter_Message_Abstract {

	const TYPE = 1;

	public function getOptions() {
		$servers = static::_getConfig()->servers;
		if (empty($servers)) {
			throw new CM_Exception_Invalid('No servers configured');
		}
		$server = $servers[array_rand($servers)];
		$sockjsUrls = $server['sockjsUrls'];
		$sockjsUrl = $sockjsUrls[array_rand($sockjsUrls)];
		if (static::_getConfig()->hostPrefix) {
			$sockjsUrl = preg_replace('~^https?://~', '${0}' . rand(1, 9999) . '.', $sockjsUrl);
		}
		return array('sockjsUrl' => $sockjsUrl);
	}

	public function publish($channel, $data) {
		$event = array('type' => 'message', 'data' => array('channel' => $channel, 'data' => $data));
		CM_Cache_Redis::publish('socket-redis-down', json_encode($event));
	}

	public function startSynchronization() {
		$adapter = $this;
		CM_Cache_Redis::subscribe('socket-redis-up', function ($channel, $message) use ($adapter) {
			$adapter->onRedisMessage($message);
		});
	}

	public function synchronize() {
		$startStampLimit = time() - 3;
		$channelsStatus = $this->_fetchStatus();
		/** @var $channelsPersistenceArray CM_Model_StreamChannel_Abstract[] */
		$channelsPersistenceArray = array();
		/** @var $channelModel CM_Model_StreamChannel_Message */
		foreach (new CM_Paging_StreamChannel_AdapterType($this->getType()) as $channelModel) {
			$channel = $channelModel->getKey() . ':' . $channelModel->getType();
			if (!isset($channelsStatus[$channel])) {
				$channelModel->delete();
			} else {
				$channelsPersistenceArray[$channel] = $channelModel;
			}
		}

		/** @var $channelsPersistenceItems CM_Model_Stream_Subscribe[] */
		$streamsPersistenceArray = array();
		/** @var $stream CM_Model_Stream_Subscribe */
		foreach (new CM_Paging_StreamSubscribe_AdapterType($this->getType()) as $stream) {
			$channelModel = $stream->getStreamChannel();
			$channel = $channelModel->getKey() . ':' . $channelModel->getType();
			if (!isset($channelsStatus[$channel]) || !isset($channelsStatus[$channel]['subscribers'][$stream->getKey()])) {
				$stream->delete();
			} else {
				$streamsPersistenceArray[$channel . '/' . $stream->getKey()] = $stream;
			}
		}

		foreach ($channelsStatus as $channel => $channelModel) {
			if (isset($channelsPersistenceArray[$channel])) {
				$streamChannel = $channelsPersistenceArray[$channel];
			} else {
				$oldSubscribers = array_filter($channelModel['subscribers'], function ($subscriber) use ($startStampLimit) {
					return $subscriber['subscribeStamp'] / 1000 <= $startStampLimit;
				});
				if (!count($oldSubscribers)) {
					continue;
				}
				list($channelKey, $channelType) = explode(':', $channel, 2);
				$streamChannel = CM_Model_StreamChannel_Message::createType($channelType, array('key' => $channelKey, 'adapterType' => $this->getType()));
			}
			foreach ($channelModel['subscribers'] as $subscriber) {
				$clientKey = (string) $subscriber['clientKey'];
				if (!isset($streamsPersistenceArray[$channel . '/' . $clientKey])) {
					$data = CM_Params::factory((array) $subscriber['data']);
					$user = null;
					if ($data->has('sessionId')) {
						if ($session = CM_Session::findById($data->getString('sessionId'))) {
							$user = $session->getUser(false);
						}
					}
					$start = (int) ($subscriber['subscribeStamp'] / 1000);
					$allowedUntil = null;
					if ($start <= $startStampLimit) {
						CM_Model_Stream_Subscribe::create(array('user'          => $user, 'start' => $start, 'allowedUntil' => $allowedUntil,
																'streamChannel' => $streamChannel, 'key' => $clientKey));
					}
				}
			}
		}
	}

	/**
	 * @param string $message
	 * @throws CM_Exception_Invalid
	 */
	public function onRedisMessage($message) {
		$message = CM_Params::decode($message, true);
		$type = $message['type'];
		$data = $message['data'];
		switch ($type) {
			case 'subscribe':
				$channelKey = $data['channel'];
				$clientKey = $data['clientKey'];
				$start = time();
				$allowedUntil = null;
				$data = CM_Params::factory((array) $data['data']);
				$user = null;
				if ($data->has('sessionId')) {
					if ($session = CM_Session::findById($data->getString('sessionId'))) {
						$user = $session->getUser(false);
					}
				}
				$this->_subscribe($channelKey, $clientKey, $start, $allowedUntil, $user);
				break;
			case 'unsubscribe':
				$channelKey = $data['channel'];
				$clientKey = $data['clientKey'];
				$this->_unsubscribe($channelKey, $clientKey);

				break;
			case 'message':

				break;
			default:
				throw new CM_Exception_Invalid('Invalid socket-redis event type');
		}
	}

	/**
	 * @param string             $channel
	 * @param string             $clientKey
	 * @param int                $start
	 * @param int                $allowedUntil
	 * @param CM_Model_User|null $user
	 * @throws CM_Exception_Invalid
	 */
	protected function _subscribe($channel, $clientKey, $start, $allowedUntil, CM_Model_User $user = null) {
		list($channelKey, $channelType) = explode(':', $channel, 2);
		$streamChannel = CM_Model_StreamChannel_Message::findByKey($channelKey, $this->getType());
		if ($streamChannel && $streamChannel->getType() != $channelType) {
			throw new CM_Exception_Invalid('streamChannel\'s `type` doesn\'t match excpected value');
		}
		if (!$streamChannel) {
			/** @var $streamChannel CM_Model_StreamChannel_Message */
			$streamChannel = CM_Model_StreamChannel_Message::createType($channelType, array('key' => $channelKey, 'adapterType' => $this->getType()));
		}
		$streamChannelSubscribes = $streamChannel->getStreamSubscribes();
		if ($streamChannelSubscribes->findKey($clientKey)) {
			return;
		}
		CM_Model_Stream_Subscribe::create(array('user'          => $user, 'start' => $start, 'allowedUntil' => $allowedUntil,
												'streamChannel' => $streamChannel, 'key' => $clientKey));
	}

	/**
	 * @param string $channelKey
	 * @param string $clientKey
	 */
	protected function _unsubscribe($channelKey, $clientKey) {
		$streamChannel = CM_Model_StreamChannel_Message::findByKey($channelKey, $this->getType());
		if (!$streamChannel) {
			return;
		}
		$streamChannelSubscribe = $streamChannel->getStreamSubscribes()->findKey($clientKey);
		if ($streamChannelSubscribe) {
			$streamChannelSubscribe->delete();
		}
		if ($streamChannel->getStreamSubscribes()->getCount() === 0) {
			$streamChannel->delete();
		}
	}

	/**
	 * @return array
	 */
	protected function _fetchStatus() {
		$servers = self::_getConfig()->servers;
		$statusData = array();
		foreach ($servers as $server) {
			$statusData = array_merge_recursive($statusData, CM_Params::decode(CM_Util::getContents('http://' . $server['httpHost'] . ':' . $server['httpPort']), true));
		}
		return $statusData;
	}
}
