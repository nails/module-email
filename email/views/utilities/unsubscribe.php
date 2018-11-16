<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="en"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="en"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>
    <title>Manage what email you receive - <?=APP_NAME?></title>
    <style type="text/css">

        html,
        body
        {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            color: #333;
            overflow: hidden;
            font-family: "Helvetica Neue", Helvetica, "Segoe UI", Arial, freesans, sans-serif;
            font-size: 16px;
            line-height: 1.6em;
        }

        #container {
            word-wrap: break-word;
            max-width: 600px;
            min-width: 200px;
            margin: 0 auto;
            padding: 30px;
            text-align: center;
        }

        h1
        {
            position: relative;
            margin: 0.67em 0;
            margin-top: 1em;
            margin-bottom: 16px;
            padding-bottom: 0.3em;
            font-size: 2.25em;
            font-weight: bold;
            line-height: 1.2;
        }

        h2
        {
            font-size: 1.1em;
        }

        h1,
        hr
        {
            border: 0;
            border-bottom: 1px solid #eee;
        }

        img
        {
            border: 0;
            max-width: 100%;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        p
        {
            margin-top: 0;
            margin-bottom: 16px;
        }

        a
        {
            background: transparent;
            color: #4183C4;
            text-decoration: none;
        }

        a:active,
        a:hover
        {
            outline: 0;
        }

        a:hover,
        a:focus,
        a:active
        {
            text-decoration: underline;
        }

        a.btn
        {
            border: 1px solid #396C9E;
            background: #4183C4;
            padding: 0.4em 0.8em;
            color: #FFFFFF;
            border-radius: 3px;
            box-shadow: 0px 0px 1px rgba(0,0,0,0.5)
        }

        a.btn:hover,
        a.btn:focus,
        a.btn:active
        {
            text-decoration: none;
            background: #396C9E;
        }

        a.btn:active
        {
            position: relative;
            top: 1px;
            box-shadow: none;
        }

        small
        {
            font-size:0.65em;
        }

        #logo-container
        {
            width: 125px;
            margin: auto;
        }

        #logo
        {
            max-width: 100%;
            height: auto;
        }

    </style>
</head>
<body>
    <div id="container">
        <?php

        $paths   = array();

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/error_404.png',
            BASE_URL . 'assets/img/error_404.png'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo.png',
            BASE_URL . 'assets/img/logo.png'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo.jpg',
            BASE_URL . 'assets/img/logo.jpg'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo.gif',
            BASE_URL . 'assets/img/logo.gif'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo/logo.png',
            BASE_URL . 'assets/img/logo/logo.png'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo/logo.jpg',
            BASE_URL . 'assets/img/logo/logo.jpg'
        );

        $paths[] = array(
            NAILS_APP_PATH . 'assets/img/logo/logo.gif',
            BASE_URL . 'assets/img/logo/logo.gif'
        );

        if (NAILS_BRANDING) {

            $paths[] = array(
                NAILS_ASSETS_PATH . 'img/nails/icon/icon@2x.png',
                NAILS_ASSETS_URL . 'img/nails/icon/icon@2x.png'
            );
        }


        foreach ($paths as $path) {

            if (is_file($path[0])) {

                ?>
                <h1>
                    <div id="logo-container">
                        <img src="<?=$path[1]?>" id="logo" />
                    </div>
                </h1>
                <?php
                break;
            }
        }


        if ($this->input->get('undo')) {

            ?>
            <h2>That's OK, we all make mistakes</h2>
            <p>
                We'll continue to send you this type of email.
            </p>
            <p>
                <?=anchor('email/unsubscribe?token=' . $this->input->get('token'), 'Unsubscribe?', 'class="btn"') ?>
            </p>
            <?php

        } else {

            ?>
            <h2>Successfully Unsubscribed</h2>
            <p>
                OK! We won't send you this type of email again.
            </p>
            <p>
                <?=anchor('email/unsubscribe?token=' . $this->input->get('token') . '&undo=1', 'Undo?', 'class="btn"') ?>
            </p>
            <?php

        }

        if (NAILS_BRANDING) {

            ?>
            <hr />
            <p>
                <small>
                    Powered by <a href="http://nailsapp.co.uk">Nails</a>
                </small>
            </p>
            <?php

        }

        ?>
    </div>
</body>