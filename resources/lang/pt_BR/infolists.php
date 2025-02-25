<?php

return [
    'components' => [
        "created_by_at" => "<strong>:causer</strong> <strong>:event</strong> <strong>:subject</strong>. <br><small> Atualizado em: <strong>:update_at</strong></small>",
        "updater_updated" => ":causer :event o seguinte: <br>:changes",
        "from_oldvalue_to_newvalue" => "-> <strong> ATUALIZAÇÃO: </strong> :key mudou de <strong>:old_value</strong> para <strong>:new_value</strong>",
        "to_newvalue" => "- :key <strong>:new_value</strong>",
        "unknown"   => "Sistema"
    ],
];