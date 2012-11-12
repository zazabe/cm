<?php

class CM_Model_SplitFeature extends CM_Model_Abstract {
	CONST TYPE = 28;

	/**
	 * @param string $name
	 */
	public function __construct($name) {
		$this->_construct(array('name' => (string) $name));
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->_getId('name');
	}

	/**
	 * @return int
	 */
	public function getId() {
		return (int) $this->_get('id');
	}

	/**
	 * @return int
	 */
	public function getPercentage() {
		return (int) $this->_get('percentage');
	}

	/**
	 * @param int $percentage
	 */
	public function setPercentage($percentage) {
		$percentage = (int) $percentage;
		$this->_checkPercentage($percentage);

		CM_Mysql::update(TBL_CM_SPLITFEATURE, array('percentage' => $percentage), array('id' => $this->getId()));
		$this->_change();
	}

	/**
	 * @param CM_Model_User $user
	 * @throws CM_Exception_Invalid
	 * @return boolean
	 */
	public function getEnabled(CM_Model_User $user) {

		$cacheKey = CM_CacheConst::SplitFeature_Fixtures . '_userId:' . $user->getId();
		$cacheWrite = false;
		if (($splitFeatureFixtures = CM_CacheLocal::get($cacheKey)) === false) {
			$splitFeatureFixtures = array();
			$allSplitFeatureFixtures = CM_Mysql::select(TBL_CM_SPLITFEATURE_FIXTURE, array('splitfeatureId', 'fixtureId'), array('userId' => $user->getId()))->fetchAll();
			foreach($allSplitFeatureFixtures as $fixture) {
				$splitFeatureFixtures['splitfeatureId'] = $fixture['fixtureId'];
			}
			$cacheWrite = true;
		}

		if (!array_key_exists($this->getId(), $splitFeatureFixtures)) {
			$fixtureId = CM_Mysql::insert(TBL_CM_SPLITFEATURE_FIXTURE, array('splitfeatureId' => $this->getId(), 'userId' => $user->getId()));
			$splitFeatureFixtures[$this->getId()] = $fixtureId;
			$cacheWrite = true;
		}

		if ($cacheWrite) {
			CM_CacheLocal::set($cacheKey, $splitFeatureFixtures);
		}

		return $this->_calculateEnabled($splitFeatureFixtures[$this->getId()]);
	}

	/**
	 * @param int $fixtureId
	 * @return bool
	 */
	protected function _calculateEnabled($fixtureId) {
		$fixtureIdInternal = $fixtureId - 1;
		return ($fixtureIdInternal % 100 < $this->getPercentage());
	}

	protected function _loadData() {
		$data = CM_Mysql::select(TBL_CM_SPLITFEATURE, '*', array('name' => $this->getName()))->fetchAssoc();
		return $data;
	}

	protected function _onDelete() {
		CM_Mysql::delete(TBL_CM_SPLITFEATURE, array('id' => $this->getId()));
		CM_Mysql::delete(TBL_CM_SPLITFEATURE_FIXTURE, array('splitfeatureId' => $this->getId()));
	}

	protected static function _create(array $data) {
		$name = (string) $data['name'];
		$percentage = (int) $data['percentage'];

		self::_checkPercentage($percentage);

		CM_Mysql::insert(TBL_CM_SPLITFEATURE, array('name' => $name, 'percentage' => $percentage));

		return new static($name);
	}

	/**
	 * @param int $percentage
	 * @throws CM_Exception_InvalidParam
	 */
	private static function _checkPercentage($percentage) {
		$percentage = (int) $percentage;

		if ($percentage < 0 || $percentage > 100) {
			throw new CM_Exception_InvalidParam('Percentage must be between 0 and 100 ' . $percentage . ' was given');
		}
	}
}
