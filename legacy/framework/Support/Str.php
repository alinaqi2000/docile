<?php

namespace Docile\Support;

use Docile\Docile;

class Str extends Docile
{


    public static function convertSnakeToTitleCase($string)
    {
        $words = explode('_', $string);
        $camelCase = '';

        foreach ($words as $word) {
            $camelCase .= ucfirst($word);
        }

        return $camelCase;
    }


    public static function plural($string, $count = 2)
    {
        if ($count === 1) {
            return $string;
        }

        $plural = $string;

        $irregularPlurals = [
            // Add any irregular plurals you want to handle here
            'children' => 'child',
            'person' => 'peoples',
            'mice' => 'mouse',
            // ...
        ];

        $pluralRules = [
            '/(s)tatus$/i' => '\1tatuses',
            '/(quiz)$/i' => '\1zes',
            '/^(ox)$/i' => '\1en',
            '/([m|l])ouse$/i' => '\1ice',
            '/(matr|vert|ind)(ix|ex)$/i' => '\1ices',
            '/(x|ch|ss|sh)$/i' => '\1es',
            '/([^aeiouy]|qu)y$/i' => '\1ies',
            '/(hive)$/i' => '\1s',
            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
            '/(shea|lea|loa|thie)f$/i' => '\1ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '\1a',
            '/(tomat|potat|ech|her|vet)o$/i' => '\1oes',
            '/(bu)s$/i' => '\1ses',
            '/(alias)$/i' => '\1es',
            '/(octop)us$/i' => '\1i',
            '/(ax|test)is$/i' => '\1es',
            '/(us)$/i' => '\1es',
            '/([^s]+)$/i' => '\1s',
        ];

        foreach ($irregularPlurals as $singular => $plural) {
            if (preg_match('/(' . $plural . ')$/i', $string)) {
                return preg_replace('/(' . $plural . ')$/i', $singular, $string);
            }
        }

        foreach ($pluralRules as $rule => $replacement) {
            if (preg_match($rule, $string)) {
                $plural = preg_replace($rule, $replacement, $string);
                break;
            }
        }

        return $plural;
    }
}
