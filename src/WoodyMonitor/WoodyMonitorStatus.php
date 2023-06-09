<?php

/**
 * Woody Monitor
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2022
 */

namespace WoodyMonitor;

use Symfony\Component\Finder\Finder;

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

        // Get View
        if (!empty($_GET['callback']) && ($_GET['callback'] == 'api' || $_GET['callback'] == 'async_count')) {
            $view = 'async_count';
        } elseif (!empty($_GET['callback']) && $_GET['callback'] == 'failed_count') {
            $view = 'failed_count';
        } elseif (!empty($_GET['callback']) && $_GET['callback'] == 'failed_list') {
            $view = 'failed_list';
        } elseif (!empty($_GET['callback']) && $_GET['callback'] == '404_count') {
            $view = '404_count';
        } elseif (!empty($_GET['callback']) && $_GET['callback'] == '404_last') {
            $view = '404_last';
        } else {
            $view = 'status';
        }

        // Get View
        if (!empty($_GET['site_key'])) {
            $param_site_key = $_GET['site_key'];
        }

        // check if there are any search results
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $pathinfo = pathinfo($file->getRealPath());
                $site_key = explode('/', $pathinfo['dirname']);
                $site_key = end($site_key);

                // On ne garde que le site_key passé en paramètre
                if (isset($param_site_key) && $param_site_key != $site_key) {
                    continue;
                }

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

                $locked = (empty($env['WOODY_ACCESS_LOCKED'])) ? false : $env['WOODY_ACCESS_LOCKED'];
                $staging = (empty($env['WOODY_ACCESS_STAGING'])) ? false : $env['WOODY_ACCESS_STAGING'];

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
                $options = (empty($env['WOODY_OPTIONS'])) ? [] : $env['WOODY_OPTIONS'];
                $all_options = array_merge($all_options, $options);

                // Status
                $all_status[$status] = (empty($all_status[$status])) ? 1 : ($all_status[$status] + 1);

                // MysqlConnect
                $mysqli = $this->mysqlConnect($env);

                // Sites
                $sites[$site_key] = [
                    'site_key' => $site_key,
                    'url' => (empty($env['WP_HOME'])) ? null : $env['WP_HOME'],
                    'status' => $status,
                    'locked' => $locked,
                    'staging' => $staging,
                    'options' => (empty($env['WOODY_OPTIONS'])) ? [] : $env['WOODY_OPTIONS'],
                ];

                if ($view == 'async_count') {
                    $sites[$site_key]['async'] = $this->getCountAsync($mysqli);
                } elseif ($view == 'failed_count') {
                    $sites[$site_key]['failed'] = $this->getCountFailed($mysqli);
                } elseif ($view == 'failed_list') {
                    $sites[$site_key]['failed'] = $this->getListFailed($mysqli);
                } elseif ($view == '404_count') {
                    $sites[$site_key]['404_count'] = $this->getCount404($mysqli);
                } elseif ($view == '404_last') {
                    $sites[$site_key]['404_last'] = $this->getLast404($mysqli);
                }
            }

            $all_options = (empty($all_options)) ? [] : array_unique($all_options);
            sort($all_options);

            if (!empty($sites)) {
                ksort($sites);
                $this->compile($view, [
                    'sites' => $this->order($this->filter($sites)),
                    'all_options' => $all_options,
                    'all_status' => $all_status,
                ]);
            }
        }
    }

    private function getCountAsync($mysqli)
    {
        $result = $mysqli->query("SELECT count(*) FROM `wp_woody_async` WHERE failed is null");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function getCountFailed($mysqli)
    {
        $result = $mysqli->query("SELECT count(*) FROM `wp_woody_async` WHERE failed is not null");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function getListFailed($mysqli)
    {
        $return = [];
        $result = $mysqli->query("SELECT * FROM `wp_woody_async` WHERE failed is not null");
        if (!empty($result)) {
            while ($row = $result->fetch_assoc()) {
                $return[] = $row;
            }
        }

        return $return;
    }

    private function getCount404($mysqli)
    {
        $result = $mysqli->query("SELECT count(*) FROM `wp_redirection_404`");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function getLast404($mysqli)
    {
        $result = $mysqli->query("SELECT count(*) FROM `wp_redirection_404` WHERE created >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        if (!empty($result)) {
            $result = $result->fetch_assoc();
            if (!empty($result['count(*)'])) {
                return $result['count(*)'];
            }
        }

        return 0;
    }

    private function mysqlConnect($env)
    {
        ini_set('display_errors', 0);

        $mysqli = new \mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASSWORD'], $env['DB_NAME']);
        if ($mysqli->connect_errno !== 0) {
            echo "Échec lors de la connexion à MySQL : (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n";
        }

        return $mysqli;
    }

    private function order($sites)
    {
        if (!empty($_GET['order']) && $_GET['order'] == 'async') {
            usort($sites, fn ($a, $b) => $b['async'] <=> $a['async']);
        } elseif (!empty($_GET['order']) && $_GET['order'] == 'failed') {
            usort($sites, fn ($a, $b) => $b['failed'] <=> $a['failed']);
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

        if (!empty($_GET['notoptions'])) {
            foreach ($sites as $site_key => $site) {
                if (in_array($_GET['notoptions'], $site['options'])) {
                    unset($sites[$site_key]);
                }
            }
        }

        return $sites;
    }

    private function compile($view, $data)
    {
        header('X-VC-TTL: 0');
        require_once($view . '.tpl.php');
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
        $env = (empty($env)) ? [] : explode(',', $env);
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
