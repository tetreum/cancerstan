<?php

namespace CancerStan;

class PHPStan {
    public static function run(?string $path = null): ?object {
        if (!$path) {
            $path = "./vendor/bin/phpstan";
        }
        $output = [];
        exec($path . " --error-format=json", $output);

        if (empty($output)) {
            return null;
        }
        $json = $output[count($output) - 1];

        return json_decode($json);
    }
}
