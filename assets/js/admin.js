'use strict';

import '../sass/admin.scss';
import UserTabSubscriptions from './components/UserTabSubscriptions.js';

(function() {
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-email',
        'UserTabSubscriptions',
        function(controller) {
            return new UserTabSubscriptions(controller);
        }
    );
})();
