<?php

namespace App\Helpers;

class ActionDefaults
{
    const DEFAULTS_PARAM      = 'action_defaults';

    const TARGET_ACTION_PARAM = 'target_action';

    /**
     * @param $name
     *
     * @return string|null
     */
    public static function getDefault($name)
    {
        $session = session();

        $defaults = $session->get(self::DEFAULTS_PARAM);
        if ($defaults) {
            if (array_key_exists($name, $defaults)) {
                return $defaults[$name];
            }
        }

        return null;
    }

    /**
     * @param $string
     *
     * @return array
     */
    public static function getDefaultsByPrefix($string)
    {
        $session = session();
        $results = [];

        $defaults = $session->get(self::DEFAULTS_PARAM);
        if ($defaults) {
            foreach ($defaults as $key => $value) {
                if (0 === strrpos($key, $string)) {
                    $results[$key] = $value;
                }
            }
        }

        return $results;
    }
}
