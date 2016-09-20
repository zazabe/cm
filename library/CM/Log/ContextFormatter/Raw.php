<?php

class CM_Log_ContextFormatter_Raw implements CM_Log_ContextFormatter_Interface {

    public function format(CM_Log_Context $context) {
        $user = $context->getUser();
        $request = $context->getHttpRequest();
        $extra = $context->getExtra();

        return [
            'user'    => $user ? $user->getId() : null,
            'request' => $request ? $request->toString() : $request,
            'extra'   => $extra
        ];
    }
}
