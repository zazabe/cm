<?php
require_once 'Util.php';

class CM_Bootloader {

	/** @var string */
	protected $_dataPrefix;

	/** @var CM_Config|null */
	private $_config = null;

	/** @var bool */
	private $_debug;

	/** @var CM_ExceptionHandling_Handler_Abstract */
	private $_exceptionHandler;

	/** @var CM_Bootloader */
	protected static $_instance;

	/**
	 * @param string      $pathRoot
	 * @throws CM_Exception_Invalid
	 */
	public function __construct($pathRoot) {
		if (self::$_instance) {
			throw new CM_Exception_Invalid('Bootloader already instantiated');
		}
		self::$_instance = $this;
		define('DIR_ROOT', $pathRoot);
		$this->_debug = (bool) getenv('CM_DEBUG');
		$this->_dataPrefix = '';
	}

	public function load() {
		$this->_constants();
		$this->_exceptionHandler();
		$this->_errorHandler();
		$this->_defaults();
	}

	/**
	 * @return CM_Config
	 */
	public function getConfig() {
		if (null === $this->_config) {
			$this->_config = new CM_Config();
		}
		return $this->_config;
	}

	/**
	 * @return CM_ExceptionHandling_Handler_Abstract
	 */
	public function getExceptionHandler() {
		if (!$this->_exceptionHandler) {
			if ($this->isCli()) {
				$this->_exceptionHandler = new CM_ExceptionHandling_Handler_Cli();
			} else {
				$this->_exceptionHandler = new CM_ExceptionHandling_Handler_Http();
			}
		}
		return $this->_exceptionHandler;
	}

	/**
	 * @return bool
	 */
	public function isDebug() {
		return $this->_debug;
	}

	/**
	 * @return bool
	 */
	public function isCli() {
		return PHP_SAPI === 'cli';
	}

	/**
	 * @return string
	 */
	public function getDataPrefix() {
		return $this->_dataPrefix;
	}

	/**
	 * @return string[]
	 */
	public function getNamespaces() {
		return array_keys($this->_getNamespacePaths());
	}

	public function reloadNamespacePaths() {
		$cacheKey = CM_CacheConst::Modules;
		$cache = new CM_Cache_Storage_Apc();
		$cache->delete($cacheKey);
	}

	/**
	 * @param string $namespace
	 * @throws CM_Exception_Invalid
	 * @return string
	 */
	public function getNamespacePath($namespace) {
		$namespacePaths = $this->_getNamespacePaths();
		if (!array_key_exists($namespace, $namespacePaths)) {
			throw new CM_Exception_Invalid('`' . $namespace . '`, not found within namespace paths');
		}
		return $namespacePaths[$namespace];
	}

	protected function _constants() {
		define('DIR_VENDOR', DIR_ROOT . 'vendor/');
		define('DIR_PUBLIC', DIR_ROOT . 'public/');

		define('DIR_DATA', DIR_ROOT . $this->getDataPrefix() . 'data/');
		define('DIR_DATA_LOCKS', DIR_DATA . 'locks/');
		define('DIR_DATA_LOG', DIR_DATA . 'logs/');
		define('DIR_DATA_SVM', DIR_DATA . 'svm/');

		define('DIR_TMP', DIR_ROOT . 'tmp/');
		define('DIR_TMP_CACHE',  DIR_TMP . 'cache/');
		define('DIR_TMP_SMARTY', DIR_TMP . 'smarty/');

		define('DIR_USERFILES', DIR_PUBLIC . $this->getDataPrefix() . 'userfiles/');
	}

	protected function _errorHandler() {
		error_reporting((E_ALL | E_STRICT) & ~(E_NOTICE | E_USER_NOTICE));
		set_error_handler(array($this->getExceptionHandler(), 'handleErrorRaw'));
	}

	protected function _exceptionHandler() {
		$errorHandler = $this->getExceptionHandler();
		set_exception_handler(function (Exception $exception) use ($errorHandler) {
			$errorHandler->handleException($exception);
			exit(1);
		});
	}

	protected function _defaults() {
		date_default_timezone_set(CM_Config::get()->timeZone);
		mb_internal_encoding('UTF-8');
		umask(0);
		CMService_Newrelic::getInstance()->setConfig();
	}

	/**
	 * @return array
	 */
	private function _getNamespacePaths() {
		$cacheKey = CM_CacheConst::Modules;
		$apcCache = new CM_Cache_Storage_Apc();
		if (false === ($namespacePaths = $apcCache->get($cacheKey))) {
			$fileCache = new CM_Cache_Storage_File();
			$installation = new CM_App_Installation();
			if ($installation->getUpdateStamp() > $fileCache->getCreateStamp($cacheKey) || false === ($namespacePaths = $fileCache->get($cacheKey))) {
				$namespacePaths = $installation->getModulePaths();
				$fileCache->set($cacheKey, $namespacePaths);
			}
			$apcCache->set($cacheKey, $namespacePaths);
		}
		return $namespacePaths;
	}

	/**
	 * @return CM_Bootloader
	 * @throws Exception
	 */
	public static function getInstance() {
		if (!self::$_instance) {
			throw new Exception('No bootloader instance');
		}
		return self::$_instance;
	}
}
