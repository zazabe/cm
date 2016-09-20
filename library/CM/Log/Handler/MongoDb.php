<?php

class CM_Log_Handler_MongoDb extends CM_Log_Handler_Abstract {

    /** @var  string */
    protected $_collection;

    /** @var int|null */
    protected $_recordTtl = null;

    /** @var  CM_MongoDb_Client */
    protected $_mongoDb;

    /** @var  array */
    protected $_insertOptions;

    /**
     * @param CM_Log_Formatter_Interface $formatter
     * @param CM_MongoDb_Client          $mongoDb
     * @param string                     $collection
     * @param int|null                   $recordTtl Time To Live in seconds
     * @param array                      $insertOptions
     * @param int|null                   $minLevel
     * @throws CM_Exception_Invalid
     */
    public function __construct(CM_Log_Formatter_Interface $formatter, CM_MongoDb_Client $mongoDb, $collection, $recordTtl = null, array $insertOptions = null, $minLevel = null) {
        parent::__construct($minLevel, $formatter);
        $this->_collection = (string) $collection;
        $this->_mongoDb = $mongoDb;
        if (null !== $recordTtl) {
            $this->_recordTtl = (int) $recordTtl;
            if ($this->_recordTtl <= 0) {
                throw new CM_Exception_Invalid('TTL should be positive value');
            }
        };
        $this->_insertOptions = null !== $insertOptions ? $insertOptions : ['w' => 0];
    }

    /**
     * @param CM_Log_Record $record
     */
    protected function _writeRecord(CM_Log_Record $record) {
        $this->_mongoDb->insert($this->_collection, $this->_formatRecord($record), $this->_insertOptions);
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
        $nonUtfBytesList = [];
        array_walk_recursive($formattedRecord, function (&$value, $key) use (&$nonUtfBytesList) {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $nonUtfBytesList[$key] = unpack('H*', $value)[1];
                $value = CM_Util::sanitizeUtf($value);
            }
        });

        if (!empty($nonUtfBytesList)) {
            $formattedRecord['loggerNotifications']['sanitizedFields'] = [];
            foreach ($nonUtfBytesList as $key => $nonUtfByte) {
                $formattedRecord['loggerNotifications']['sanitizedFields'][$key] = $nonUtfByte;
            }
        }
        return $formattedRecord;
    }
}
