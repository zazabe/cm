<?php

abstract class CM_Log_Formatter_Abstract implements CM_Log_Formatter_Interface {

    /** @var  CM_Log_ContextFormatter_Interface */
    protected $_contextFormatter;

    /** @var  string */
    protected $_formatDate;

    /**
     * @param CM_Log_ContextFormatter_Interface $contextFormatter
     * @param string $formatDate
     */
    public function __construct(CM_Log_ContextFormatter_Interface $contextFormatter,  $formatDate = null) {
        $formatDate = null !== $formatDate ? $formatDate : DateTime::ISO8601;
        $this->_contextFormatter = $contextFormatter;
        $this->_formatDate = $formatDate;
    }

    /**
     * @return CM_Log_ContextFormatter_Interface
     */
    public function getContextFormatter() {
        return $this->_contextFormatter;
    }

    /**
     * @param CM_Log_Record $record
     * @return array
     */
    public function format(CM_Log_Record $record) {
        $context = $record->getContext();
        return [
            'message'   => $record->getMessage(),
            'level'     => strtolower(CM_Log_Logger::getLevelName($record->getLevel())),
            'timestamp' => $record->getCreatedAt()->format($this->_formatDate),
            'context'   => $this->getContextFormatter()->format($context)
        ];
    }
}
