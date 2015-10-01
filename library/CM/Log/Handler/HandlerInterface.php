<?php

interface CM_Log_Handler_HandlerInterface {

    /**
     * @return int
     */
    public function getLevel();

    /**
     * @param CM_Log_Record $record
     * @return bool Whether the record was successfully handled
     */
    public function handleRecord(CM_Log_Record $record);
}