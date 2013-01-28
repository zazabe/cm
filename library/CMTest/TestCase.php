<?php

abstract class CMTest_TestCase extends PHPUnit_Framework_TestCase {
	/**
	 * @param string $table
	 * @param array  $where WHERE conditions: ('attr' => 'value', 'attr2' => 'value')
	 * @param int    $rowCount
	 */
	public static function assertRow($table, $where = null, $rowCount = 1) {
		$result = CM_Mysql::select($table, '*', $where);
		self::assertEquals($rowCount, $result->numRows());
	}

	public static function assertNotRow($table, $columns) {
		self::assertRow($table, $columns, 0);
	}

	public static function assertEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = false, $ignoreCase = true) {
		if ($expected instanceof CM_Comparable) {
			self::assertTrue($expected->equals($actual), 'Models differ');
		} else {
			parent::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
		}
	}

	public static function assertNotEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE) {
		if ($expected instanceof CM_Comparable) {
			self::assertFalse($expected->equals($actual), 'Models do not differ');
		} else {
			parent::assertNotEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
		}
	}

	/**
	 * @param mixed         $needle
	 * @param Traversable   $haystack
	 * @param string        $message
	 * @param boolean       $ignoreCase
	 * @param boolean       $checkForObjectIdentity
	 * @throws CM_Exception_Invalid
	 */
	public static function assertNotContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true) {
		if ($needle instanceof CM_Comparable) {
			if (!(is_array($haystack) || $haystack instanceof Traversable)) {
				throw new CM_Exception_Invalid('Haystack is not traversable.');
			}
			$match = false;
			foreach ($haystack as $hay) {
				if ($needle->equals($hay)) {
					$match = true;
					break;
				}
			}
			self::assertFalse($match, 'Needle contained.');
		} else {
			parent::assertNotContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity);
		}
	}

	/**
	 * @param CM_Comparable $needle
	 * @param Traversable   $haystack
	 * @param string        $message
	 * @param boolean       $ignoreCase
	 * @param boolean       $checkForObjectIdentity
	 * @throws CM_Exception_Invalid
	 */
	public static function assertContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true) {
		if ($needle instanceof CM_Comparable) {
			if (!(is_array($haystack) || $haystack instanceof Traversable)) {
				throw new CM_Exception_Invalid('Haystack is not traversable.');
			}
			$match = false;
			foreach ($haystack as $hay) {
				if ($needle->equals($hay)) {
					$match = true;
					break;
				}
			}
			self::assertTrue($match, 'Needle not contained.');
		} else {
			parent::assertContains($needle, $haystack, $message, $ignoreCase, $checkForObjectIdentity);
		}
	}

	/**
	 * @param array $needles
	 * @param mixed $haystack
	 */
	public static function assertContainsAll(array $needles, $haystack) {
		foreach ($needles as $needle) {
			self::assertContains($needle, $haystack);
		}
	}

	/**
	 * @param array $needles
	 * @param mixed $haystack
	 */
	public static function assertNotContainsAll(array $needles, $haystack) {
		foreach ($needles as $needle) {
			self::assertNotContains($needle, $haystack);
		}
	}

	/**
	 *
	 * @param array $needles
	 * @param array $haystacks
	 */
	public static function assertArrayContains(array $needles, array $haystacks) {
		if (count($haystacks) < count($needles)) {
			self::fail('not enough elements to compare each');
		}
		for ($i = 0; $i < count($needles); $i++) {
			self::assertContains($needles[$i], $haystacks[$i]);
		}
	}

	/**
	 * @param number          $expected
	 * @param number          $actual
	 * @param number|null
	 */
	public static function assertSameTime($expected, $actual, $delta = null) {
		if (null === $delta) {
			$delta = 1;
		}
		self::assertEquals($expected, $actual, '', $delta);
	}

	/**
	 * @param array|null $namespaces
	 * @return CM_Site_Abstract
	 */
	protected function _getSite(array $namespaces = null) {
		if (null === $namespaces) {
			$namespaces = array('CM');
		}
		/** @var CM_Site_Abstract $site */
		$site = $this->getMockForAbstractClass('CM_Site_Abstract', array(), '', true, true, true, array('getId', 'getNamespaces'));
		$site->expects($this->any())->method('getId')->will($this->returnValue(1));
		$site->expects($this->any())->method('getNamespaces')->will($this->returnValue($namespaces));
		return $site;
	}

	/**
	 * @param string               $formClassName
	 * @param string               $actionName
	 * @param array                $data
	 * @param string|null          $componentClassName Component that uses that form
	 * @param CM_Model_User|null   $viewer
	 * @param array|null           $componentParams
	 * @param CM_Request_Post|null $requestMock
	 * @param int|null             $siteId
	 * @return mixed
	 */
	public function getMockFormResponse($formClassName, $actionName, array $data, $componentClassName = null, CM_Model_User $viewer = null, array $componentParams = null, &$requestMock = null, $siteId = null) {
		if (null === $componentParams) {
			$componentParams = array();
		}
		if (null === $siteId) {
			$siteId = $this->_getSite()->getId();
		}

		$requestArgs = array('uri' => '/form/' . $siteId);
		$requestMock = $this->getMockBuilder('CM_Request_Post')->setConstructorArgs($requestArgs)->setMethods(array('getViewer',
			'getQuery'))->getMock();
		$requestMock->expects($this->any())->method('getViewer')->will($this->returnValue($viewer));
		$viewArray = array('className' => $componentClassName, 'params' => $componentParams, 'id' => 'mockFormComponentId');
		$formArray = array('className' => $formClassName, 'params' => array(), 'id' => 'mockFormId');
		$requestMock->expects($this->any())->method('getQuery')->will($this->returnValue(array('view' => $viewArray, 'form' => $formArray,
			'actionName' => $actionName, 'data' => $data)));
		$response = new CM_Response_View_Form($requestMock);
		$response->process();
		$responseArray = json_decode($response->getContent(), true);
		return $responseArray['success'];
	}

	/**
	 * @return CM_Form_Abstract
	 */
	public function getMockForm() {
		$formMock = $this->getMockForAbstractClass('CM_Form_Abstract');
		$formMock->expects($this->any())->method('getName')->will($this->returnValue('formName'));
		$formMock->frontend_data['auto_id'] = 'formId';
		return $formMock;
	}
}
