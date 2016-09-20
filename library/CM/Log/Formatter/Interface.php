<?php

interface CM_Log_Formatter_Interface {

    /**
     * @param CM_Log_Record $record
     * @return mixed
     */
    public function format(CM_Log_Record $record);
}
