<?php

abstract class CM_Log_Handler_Abstract implements CM_Log_Handler_HandlerInterface {

    /** @var int */
    protected $_minLevel;

    /** @var CM_Log_Formatter_Interface */
    protected $_formatter;

    /**
     * @param int|null                        $minLevel
     * @param CM_Log_Formatter_Interface $formatter
     */
    public function __construct($minLevel = null, CM_Log_Formatter_Interface $formatter) {
        $minLevel = null === $minLevel ? CM_Log_Logger::DEBUG : (int) $minLevel;
        $this->setMinLevel($minLevel);
        $this->_formatter = $formatter;
    }

    /**
     * @return CM_Log_Formatter_Interface
     */
    public function getFormatter() {
        return $this->_formatter;
    }

    /**
     * @param CM_Log_Record $record
     * @return mixed
     */
    protected function _formatRecord(CM_Log_Record $record) {
        return $this->getFormatter()->format($record);
    }

    /**
     * @return int
     */
    public function getMinLevel() {
        return $this->_minLevel;
    }

    /**
     * @param int $level
     * @throws CM_Exception_Invalid
     */
    public function setMinLevel($level) {
        $level = (int) $level;
        if (CM_Log_Logger::hasLevel($level)) {
            $this->_minLevel = $level;
        }
    }

    public function isHandling(CM_Log_Record $record) {
        return $record->getLevel() >= $this->getMinLevel();
    }

    public function handleRecord(CM_Log_Record $record) {
        if ($this->isHandling($record)) {
            $this->_writeRecord($record);
        }
    }

    /**
     * @param CM_Log_Record $record
     */
    abstract protected function _writeRecord(CM_Log_Record $record);
}
