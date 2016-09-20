<?php

class CM_Log_Formatter_Raw extends CM_Log_Formatter_Abstract {

    /**
     * CM_Log_Formatter_Raw constructor.
     * @param string|null $formatDate
     */
    public function __construct($formatDate = null) {
        $contextFormatter = new CM_Log_ContextFormatter_Raw();
        parent::__construct($contextFormatter, $formatDate);
    }
}
