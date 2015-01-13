    <div class="footer">
    <?php

        $links = array();

        //  View Online link & Unsubscribe
        if (!empty($email_ref)) {

            //  Generate the hash
            $time = time();
            $hash = $email_ref . '/' . $time . '/' . md5($time . $secret . $email_ref);

            //  Link
            $links[] = anchor('email/view_online/' . $hash, 'View this E-mail Online');
        }

        // --------------------------------------------------------------------------

        //  1-Click unsubscribe
        $loginUrl = $sent_to->login_url . '?return_to=';
        $return    = '/email/unsubscribe?token=';

        /**
         * Bit of a hack; keep trying until there's no + symbol in the hash, try up to
         * 20 times before giving up @TODO: make this less hacky
         */

        $counter = 0;
        $attemps = 20;

        do {

            $token = $this->encrypt->encode($email_type->slug . '|' . $email_ref . '|' . $sent_to->email, $secret);
            $counter++;

        } while($counter <= $attemps && strpos($token, '+') !== false);

        //  Link, autologin if possible
        if (!empty($sent_to->login_url)) {

            $url = $loginUrl . urlencode($return . $token);

        } else {

            $url = $return . $token;
        }

        $links[] = anchor($url, 'Unsubscribe');

        // --------------------------------------------------------------------------

        //  Render
        if ($links) {

            echo '<p><small>';
            echo implode(' | ', $links);
            echo '</small></p>';
        }

        // --------------------------------------------------------------------------

        //  Tracker, production only
        if (strtoupper(ENVIRONMENT) == 'PRODUCTION' && !$ci->user_model->is_admin() && !$ci->user_model->was_admin()) {

            $time   = time();
            $imgSrc = site_url('email/tracker/' . $email_ref . '/' . $time . '/' . md5($time . $secret . $email_ref));
            echo '<img src="' . $imgSrc . '/tracker.gif" width="0" height="0" style="width:0px;height:0px;"">';
        }

    ?>
    </div>
    </div>
    </div>
    </body>
</html>