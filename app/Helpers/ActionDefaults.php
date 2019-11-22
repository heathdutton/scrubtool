<?php

namespace App\Helpers;

class ActionDefaults
{
    const DEFAULTS_PARAM      = 'action_defaults';

    const TARGET_ACTION_PARAM = 'target_action';

    public static function getDefault($name)
    {
        $session = session();

        if ($session->has(self::DEFAULTS_PARAM)) {
            if (array_key_exists($name, $session->get(self::DEFAULTS_PARAM))) {
                return $session->get(self::DEFAULTS_PARAM)[$name];
            }
        }

        return null;
    }
}
