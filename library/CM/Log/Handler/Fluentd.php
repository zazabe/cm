<?php

use Fluent\Logger\FluentLogger;

class CM_Log_Handler_Fluentd extends CM_Log_Handler_Abstract {

    /** @var \Fluent\Logger\FluentLogger */
    protected $_fluentdLogger;

    /** @var string */
    protected $_tag;

    /**
     * @param FluentLogger               $fluentdLogger
     * @param CM_Log_Formatter_Interface $contextFormatter
     * @param string                     $tag
     * @param int|null                   $minLevel
     */
    public function __construct(CM_Log_Formatter_Interface $formatter, FluentLogger $fluentdLogger, $tag, $minLevel = null) {
        parent::__construct($minLevel, $formatter);
        $this->_fluentdLogger = $fluentdLogger;
        $this->_tag = (string) $tag;
    }

    /**
     * @return \Fluent\Logger\FluentLogger
     */
    protected function _getFluentd() {
        return $this->_fluentdLogger;
    }

    /**
     * @param CM_Log_Record $record
     */
    protected function _writeRecord(CM_Log_Record $record) {
        $this->_getFluentd()->post($this->_tag, $this->_formatRecord($record));
    }

    /**
     * @param CM_Log_Record $record
     * @return mixed
     */
    protected function _formatRecord(CM_Log_Record $record) {
        $formattedRecord = $this->getFormatter()->format($record);
        return $this->_sanitizeRecord($formattedRecord);
    }

    /**
     * @param array $formattedRecord
     * @return array
     */
    protected function _sanitizeRecord(array $formattedRecord) {
        array_walk_recursive($formattedRecord, function (&$value, $key) {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $value = CM_Util::sanitizeUtf($value);
            }
        });
        return $formattedRecord;
    }
}
