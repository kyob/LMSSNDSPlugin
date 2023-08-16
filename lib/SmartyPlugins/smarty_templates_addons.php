<?php
$SMARTY->registerPlugin('function', 'smarty_long2ip', '_smarty_function_smarty_long2ip');

function _smarty_function_smarty_long2ip($params, $SMARTY)
{
    return long2ip($params["long"]);
}
