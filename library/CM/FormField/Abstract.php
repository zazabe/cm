<?php

abstract class CM_FormField_Abstract extends CM_View_Abstract {

    /**
     * @var mixed
     */
    private $_value;

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var array
     */
    protected $_tplParams = array();

    abstract protected function _setup();

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_setup();
    }

    /**
     * @return mixed|null Internal value
     */
    public function getValue() {
        return $this->_value;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * @param mixed $value Internal value
     */
    public function setValue($value) {
        $this->_value = $value;
    }

    /**
     * @param string $userInput
     * @return bool
     */
    public function isEmpty($userInput) {
        if (is_array($userInput) && !empty($userInput)) {
            return false;
        }
        if (is_scalar($userInput) && strlen(trim($userInput)) > 0) {
            return false;
        }
        return true;
    }

    /**
     * Filter-out invalid input data depending on of the field type.
     * For text field this will remove invalid UTF8 chars etc.
     * @param mixed $userInput
     * @return mixed
     */
    public function filterInput($userInput) {
        return $userInput;
    }

    /**
     * @param string|array         $userInput
     * @param CM_Response_Abstract $response
     * @return mixed Internal value
     * @throws CM_Exception_FormFieldValidation
     */
    abstract public function validate($userInput, CM_Response_Abstract $response);

    /**
     * @param CM_Params       $renderParams
     * @param CM_ViewResponse $viewResponse
     */
    public function prepare(CM_Params $renderParams, CM_ViewResponse $viewResponse) {
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return CM_FormField_Abstract
     */
    public function setTplParam($key, $value) {
        $this->_tplParams[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getTplParams() {
        return $this->_tplParams;
    }

    public function ajax_validate(CM_Params $params, CM_ComponentFrontendHandler $handler, CM_Response_View_Ajax $response) {
        $formName = $params->getString('form');
        $fieldName = $params->getString('fieldName');
        $userInput = $params->get('userInput');

        $form = CM_Form_Abstract::factory($formName);
        $form->setup();
        $field = $form->getField($fieldName);

        $field->validate($userInput, $response);
    }
}

