<?php

class CM_Log_Handler_NewRelic extends CM_Log_Handler_Abstract {

    /** @var CMService_Newrelic */
    protected $_newRelic;

    /**
     * @param CM_Log_Formatter_Interface $formatter
     * @param CMService_Newrelic         $newRelic
     * @param int|null                   $minLevel
     */
    public function __construct(CM_Log_Formatter_Interface $formatter, CMService_Newrelic $newRelic, $minLevel) {
        parent::__construct($formatter, $minLevel);
        $this->_newRelic = $newRelic;
    }

    public function isHandling(CM_Log_Record $record) {
        if (true !== $this->_newRelic->getEnabled()) {
            return false;
        }
        if (!$record->getContext()->getException()) {
            return false;
        }
        return parent::isHandling($record);
    }

    protected function _writeRecord(CM_Log_Record $record) {
        $this->_newRelic->setNoticeError($record->getContext()->getException());
    }
}
