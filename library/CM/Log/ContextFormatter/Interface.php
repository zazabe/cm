<?php

interface CM_Log_ContextFormatter_Interface {

    /**
     * @param CM_Log_Context $context
     * @return mixed
     */
    public function format(CM_Log_Context $context);
}
