<?php

class CM_Log_Handler_Stream extends CM_Log_Handler_Abstract {

    /** @var CM_OutputStream_Interface */
    protected $_stream;

    /**
     * @param CM_OutputStream_Interface  $stream
     * @param CM_Log_Formatter_Interface $formatter
     * @param int|null                   $minLevel
     */
    public function __construct(CM_Log_Formatter_Interface $formatter, CM_OutputStream_Interface $stream, $minLevel = null) {
        parent::__construct($minLevel, $formatter);
        $this->_stream = $stream;
    }

    /**
     * @param CM_Log_Record $record
     */
    protected function _writeRecord(CM_Log_Record $record) {
        $this->_stream->writeln($this->_formatRecord($record));
    }
}
