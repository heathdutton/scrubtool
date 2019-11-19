<?php

namespace App\Helpers;

use App\Http\Controllers\ActionDefaultsController;

class ActionDefaults
{
    const TARGET_ACTION_PARAM = 'target_action';
    const DEFAULTS_PARAM      = 'action_defaults';

    public static function getDefault($name)
    {
        $session = session();

        if ($session->has(self::DEFAULTS_PARAM)) {
            if (array_key_exists($name, $session->get(self::DEFAULTS_PARAM))) {
                return $session->get(self::DEFAULTS_PARAM)[$name];
            } else {
                return null;
            }
        }
    }
}
