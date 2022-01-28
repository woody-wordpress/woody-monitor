<html>
<head>
    <title>Woody Status</title>
    <?php if (!empty($_GET['order']) && $_GET['order'] == 'async'): ?>
    <meta http-equiv="refresh" content="15">
    <?php endif; ?>
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
            padding: 5px;
            /* border: 1px solid #fff; */
        }

        th {
            padding: 15px 5px;
            text-align: left;
        }

        td.check {
            background: #fff;
        }

        td.uncheck {
            background: rgba(255, 255, 255, 0.1);
        }

        tr {
            margin: 5px 0;
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

        .empty .check {
            background: rgba(255, 255, 255, 0.1);
        }

        .status a:hover {
            color: rgba(224, 0, 88, 1);
        }

        .empty .status a:hover,
        .staging .status a:hover {
            color: rgba(224, 0, 88, 1);
            background: #FFF;
        }

        select {
            padding: 0px 10px 0 5px;
            border-radius: 5px;
            border: 5px solid #fff;
        }

        select.order {
            background: transparent;
            border: 5px solid transparent;
            color: #fff;
        }

        .tooltip {
            position: relative;
            cursor: pointer;
        }

        .tooltiptext {
            visibility: hidden;
            background-color: black;
            white-space: nowrap;
            color: #fff;
            text-align: center;
            border-radius: 3px;
            padding: 5px;
            top:30px;
            left:0;
            position: absolute;
            z-index: 1;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
        }
    </style>
</head>
<body>
    <img src="woody_logo_white.svg" class="logo" alt="Woody">

    <form method="GET">
    <table class="cards">
        <tr class="header">
            <th colspan="<?php print(count($data['all_options']) + 3); ?>">
                <select name="status" onchange="this.form.submit()">
                <option value="">Tous les status</option>
                    <?php foreach ($data['all_status'] as $status => $nb_status) : ?>
                        <option value="<?php print $status; ?>"<?php (!empty($_GET['status']) && $_GET['status'] == $status) ? print 'selected' : null; ?>><?php print $this->__($status) . ' (' . $nb_status . ' sites)'; ?></option>
                    <?php endforeach; ?>
                <select>
                <select name="options" onchange="this.form.submit()">
                <option value="">Toutes les options</option>
                    <?php foreach ($data['all_options'] as $option) : ?>
                        <option value="<?php print $option; ?>"<?php (!empty($_GET['options']) && $_GET['options'] == $option) ? print 'selected' : null; ?>><?php print $option; ?></option>
                    <?php endforeach; ?>
                <select>
                <select class="order" name="order" onchange="this.form.submit()">
                    <option value="alpha"<?php (!empty($_GET['order']) && $_GET['order'] == 'alpha') ? print 'selected' : null; ?>>Ordre Alphabétique</option>
                    <option value="async"<?php (!empty($_GET['order']) && $_GET['order'] == 'async') ? print 'selected' : null; ?>>Par nombre d'async</option>
                <select>
            </th>
        </tr>
        <?php foreach ($data['sites'] as $site) : ?>
            <tr class="card <?php print $site['status']; ?>">
                <td class="status">
                    <a href="<?php print $site['url']; ?>" target="_blank">
                        <?php print $this->__($site['status']); ?>
                    </a>
                </td>
                <td class="site_key"><?php print $site['site_key']; ?></td>
                <?php foreach ($data['all_options'] as $option) : ?>
                    <?php if (in_array($option, $site['options'])) : ?>
                        <td class="check tooltip">&nbsp;<span class="tooltiptext"><?php print $option; ?></span></td>
                    <?php else: ?>
                        <td class="uncheck tooltip">&nbsp;<span class="tooltiptext"><?php print $option; ?></span></td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <td class="async">&nbsp;&nbsp;<?php print $site['async']; ?> async</td>
            </tr>
        <?php endforeach; ?>
    </table>
    </form>
</body>
</html>
