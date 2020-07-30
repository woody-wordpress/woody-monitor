<html>
<head>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #FFF;
            margin: 0;
            padding: 0;
            background: rgb(249, 185, 42);
            background: linear-gradient(144deg, rgba(249, 185, 42, 1) 0%, rgba(224, 0, 88, 1) 100%);
        }

        .logo {
            display: block;
            margin: 30px auto;
            width: 200px;
        }

        .cards {
            margin: 30px auto;
            /* background: rgba(255, 255, 255, 0.5); */
        }

        td {
            padding: 5px 15px;
        }

        .status a {
            border-radius: 4px;
            padding: 3px 8px;
            text-decoration: none;
            background: #FFF;
            color: #000;
        }

        .locked .status a {
            background: #000;
            color: #FFF;
        }

        .empty .status a,
        .staging .status a {
            background: transparent;
            color: #FFF;
            border: 1px dotted #FFF;
        }

        .empty {
            opacity: .3;
        }

        .status a:hover {
            color: rgba(224, 0, 88, 1);
        }

        .empty .status a:hover,
        .staging .status a:hover {
            color: rgba(224, 0, 88, 1);
            background: #FFF;
        }

        .option {
            width: 15px;
            float: left;
            margin: 0 5px;
            padding: 3px 8px;
            border-radius: 4px;
            background: #FFF;
        }

    </style>
</head>
<body>
    <img src="woody_logo_white.svg" class="logo" alt="Woody">

    <table class="cards">
        <?php foreach ($sites as $site) : ?>
            <tr class="card <?php print $site['status']; ?>">
                <td class="status">
                    <a href="<?php print $site['url']; ?>" target="_blank">
                        <?php print $this->__($site['status']); ?>
                    </a>
                </td>
                <td class="site_key"><?php print $site['site_key']; ?></td>
                <td class="options">
                    <?php foreach ($site['options'] as $option) : ?>
                        <?php if (file_exists(WP_WEBROOT_DIR . '/app/plugins/woody-plugin/dist/Plugin/Resources/Assets/img/logo_' . $option . '.png')) : ?>
                            <img src="/app/plugins/woody-plugin/dist/Plugin/Resources/Assets/img/logo_<?php print $option; ?>.png" class="option" title="<?php print $option; ?>" alt="<?php print $option; ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
