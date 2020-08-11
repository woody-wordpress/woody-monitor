<?php

namespace WoodyMonitor;

use Symfony\Component\Finder\Finder;

/**
 * Deploy
 *
 * @author Léo POIROUX <leo@raccourci.fr>
 * @copyright (c) 2020, Raccourci Agency
 * @package woody-cli
 */
class WoodyMonitorStatus
{
    /**
     * __construct()
     */
    public function __construct()
    {
        \Env::init();
        $finder = new Finder();
        $finder->files()->followLinks()->ignoreDotFiles(false)->in(WP_ROOT_DIR . '/config/sites')->name('.env');

        // check if there are any search results
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $pathinfo = pathinfo($file->getRealPath());
                $site_key = explode('/', $pathinfo['dirname']);
                $site_key = end($site_key);
                $env = $this->dotenv($file->getRealPath());

                $locked = (!empty($env['WOODY_ACCESS_LOCKED'])) ? $env['WOODY_ACCESS_LOCKED'] : false;
                $staging = (!empty($env['WOODY_ACCESS_STAGING'])) ? $env['WOODY_ACCESS_STAGING'] : false;

                if (!file_exists(WP_ROOT_DIR . '/web/app/themes/' . $site_key . '/style.css')) {
                    $status = 'empty';
                } elseif ($staging) {
                    $status = 'staging';
                } elseif ($locked) {
                    $status = 'locked';
                } else {
                    $status = 'opened';
                }

                $sites[$site_key] = [
                    'site_key' => $site_key,
                    'url' => (!empty($env['WP_HOME'])) ? $env['WP_HOME'] : null,
                    'status' => $status,
                    'locked' => $locked,
                    'staging' => $staging,
                    'options' => (!empty($env['WOODY_OPTIONS'])) ? $env['WOODY_OPTIONS'] : [],
                ];
            }

            ksort($sites);
            $this->compile($sites);
        }
    }

    private function compile($sites)
    {
        require_once('status.tpl.php');
    }

    private function debug($debug)
    {
        print '<pre>';
        print_r($debug);
        print '</pre>';
    }

    private function array_env($env)
    {
        $env = str_replace(array('[', ']', '"', ' '), '', $env);
        $env = (!empty($env)) ? explode(',', $env) : [];
        sort($env);
        return array_unique($env);
    }

    private function dotenv($file)
    {
        $env = [];
        $file = file_get_contents($file);
        $file = explode("\n", $file);
        foreach ($file as $line) {
            if (!empty($line)) {
                $line = explode('=', $line);
                $key = $line[0];
                $val = substr(substr($line[1], 1), 0, -1);

                if (substr($val, 0, 1) == '[' && substr($val, -1) == ']') {
                    $val = $this->array_env($val);
                } elseif (strpos($val, 'false') !== false) {
                    $val = false;
                } elseif (strpos($val, 'true') !== false) {
                    $val = true;
                }

                $env[$key] = $val;
            }
        }
        return $env;
    }

    private function __($str)
    {
        switch ($str) {
            case 'empty':
                return 'absent';
                break;
            case 'locked':
                return 'fermé';
                break;
            case 'opened':
                return 'ouvert';
                break;
        }

        return $str;
    }
}
