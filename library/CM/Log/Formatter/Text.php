<?php

class CM_Log_Formatter_Text extends CM_Log_Formatter_Raw {

    /** @var  string */
    protected $_formatMessage;

    /**
     * @param string|null                       $formatMessage
     * @param string|null                       $formatDate
     */
    public function __construct($formatMessage = null, $formatDate = null) {
        $contextFormatter = new CM_Log_ContextFormatter_Text();
        parent::__construct($contextFormatter, $formatDate);
        $formatMessage = null !== $formatMessage ? (string) $formatMessage : '[{timestamp} - {level}] {message}';
        $this->_formatMessage = $formatMessage;
    }

    /**
     * @param CM_Log_Record $record
     * @return string
     */
    public function format(CM_Log_Record $record) {
        $rawRecord = parent::format($record);
        $formattedRecord = $this->_formatMessage($rawRecord);
        if($rawRecord['context']) {
            $formattedRecord .= PHP_EOL . $rawRecord['context'];
        }
        return $formattedRecord;
    }

    /**
     * @param array $rawRecord
     * @return string
     */
    protected function _formatMessage(array $rawRecord) {
        return $this->_format($this->_formatMessage,  $rawRecord);
    }

    /**
     * @param string $text
     * @param array  $data
     * @return string
     */
    protected function _format($text, array $data) {
        $text = (string) $text;
        return preg_replace_callback('/\{([a-z]+)\}/i', function ($matches) use ($data) {
            return isset($data[$matches[1]]) ? $data[$matches[1]] : '';
        }, $text);
    }
}
