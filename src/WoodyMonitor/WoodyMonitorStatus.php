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

        // Defin vars
        $all_options = [];
        $all_status = [];

        // check if there are any search results
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $pathinfo = pathinfo($file->getRealPath());
                $site_key = explode('/', $pathinfo['dirname']);
                $site_key = end($site_key);
                $env = $this->dotenv($file->getRealPath());

                if (file_exists(WP_WEBROOT_DIR . '/app/themes/' . $site_key . '/config/' . $env['WP_ENV'] . '/.env')) {
                    $finder_theme = new Finder();
                    $finder_theme->files()->followLinks()->ignoreDotFiles(false)->in(WP_WEBROOT_DIR . '/app/themes/' . $site_key . '/config/' . $env['WP_ENV'])->name('.env');
                    if ($finder_theme->hasResults()) {
                        foreach ($finder_theme as $file_theme) {
                            $env_theme = $this->dotenv($file_theme->getRealPath());
                            $env = array_merge($env, $env_theme);
                        }
                    }
                }

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

                // Options
                $options = (!empty($env['WOODY_OPTIONS'])) ? $env['WOODY_OPTIONS'] : [];
                $all_options = array_merge($all_options, $options);

                // Status
                $all_status[$status] = (empty($all_status[$status])) ? 1 : ($all_status[$status] + 1);

                // Sites
                $sites[$site_key] = [
                    'site_key' => $site_key,
                    'url' => (!empty($env['WP_HOME'])) ? $env['WP_HOME'] : null,
                    'status' => $status,
                    'locked' => $locked,
                    'staging' => $staging,
                    'options' => (!empty($env['WOODY_OPTIONS'])) ? $env['WOODY_OPTIONS'] : [],
                    'async' => $this->getAsync($env, $site_key),
                    'failed' => $this->getFailed($env, $site_key),
                ];
            }

            $all_options = array_unique($all_options);
            sort($all_options);
            ksort($sites);

            header('X-VC-TTL: 0');
            $this->compile([
                'sites' => $this->order($this->filter($sites)),
                'all_options' => $all_options,
                'all_status' => $all_status,
            ]);
        }
    }

    private function getAsync($env, $site_key)
    {
        $mysqli = new \mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASSWORD'], $env['DB_NAME']);
        if ($mysqli->connect_errno) {
            echo "Échec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }

        $result = $mysqli->query("SELECT count(*) FROM `wp_woody_async`");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function getFailed($env, $site_key)
    {
        $mysqli = new \mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASSWORD'], $env['DB_NAME']);
        if ($mysqli->connect_errno) {
            echo "Échec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }

        $result = $mysqli->query("SELECT count(*) FROM `wp_woody_async` WHERE failed is not null");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function order($sites)
    {
        if (!empty($_GET['order']) && $_GET['order'] == 'async') {
            usort($sites, function ($a, $b) {
                if ($a['async'] == $b['async']) {
                    return 0;
                }
                return ($a['async'] > $b['async']) ? -1 : 1;
            });
        } elseif (!empty($_GET['order']) && $_GET['order'] == 'failed') {
            usort($sites, function ($a, $b) {
                if ($a['failed'] == $b['failed']) {
                    return 0;
                }
                return ($a['failed'] > $b['failed']) ? -1 : 1;
            });
        }

        return $sites;
    }

    private function filter($sites)
    {
        if (!empty($_GET['status'])) {
            foreach ($sites as $site_key => $site) {
                if ($_GET['status'] !== $site['status']) {
                    unset($sites[$site_key]);
                }
            }
        }

        if (!empty($_GET['options'])) {
            foreach ($sites as $site_key => $site) {
                if (!in_array($_GET['options'], $site['options'])) {
                    unset($sites[$site_key]);
                }
            }
        }

        return $sites;
    }

    private function compile($data)
    {
        if (!empty($_GET['callback']) && ($_GET['callback'] == 'api' || $_GET['callback'] == 'async')) {
            require_once('async.tpl.php');
        } elseif (!empty($_GET['callback']) && $_GET['callback'] == 'failed') {
            require_once('failed.tpl.php');
        } else {
            require_once('status.tpl.php');
        }
    }

    private function debug($debug, $exit = true)
    {
        print '<pre>';
        print_r($debug);
        print '</pre>';
        if ($exit) {
            exit();
        }
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
                $val = $line[1];
                if (substr($val, 0, 1) == '"' || substr($val, 0, 1) == "'") {
                    $val = substr(substr($val, 1), 0, -1);
                }
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
