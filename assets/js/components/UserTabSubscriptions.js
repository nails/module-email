class UserTabSubscriptions {

    /**
     * Construct UserTabSubscriptions
     */
    constructor(adminController) {
        this.adminController = adminController;
        this.init();
    }

    init() {
        $('.js-email-subscriptions')
            .find('.js-subscribe-action')
            .on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                let $btn = $(e.currentTarget);
                let $row = $btn.closest('.js-row');

                let type = $row.data('type');
                let userId = $row.closest('.js-email-subscriptions').data('user-id');

                switch ($btn.data('action')) {
                    case 'subscribe':
                        this.subscribe(userId, type, $row);
                        break;

                    case 'unsubscribe':
                        this.unsubscribe(userId, type, $row);
                        break;
                }

            });
    }

    subscribe(userId, type, $row) {
        if (this.api(userId, type, 'subscribe')) {
            this
                .setLabel($row, 'Subscribed')
                .setStatus($row, 'success')
                .setHint($row, 'User will receive this type of email')
                .showUnsubscribeButton($row);
        }
    }

    unsubscribe(userId, type, $row) {
        if (this.api(userId, type, 'unsubscribe')) {
            this
                .setLabel($row, 'Unsubscribed')
                .setStatus($row, 'danger')
                .setHint($row, 'User will not received this type of email')
                .showSubscribeButton($row);
        }
    }

    async api(userId, type, action) {

        try {

            let url;
            if (action === 'subscribe') {
                url = `${window.SITE_URL}/api/email/admin/subscribe`;
            } else if (action === 'unsubscribe') {
                url = `${window.SITE_URL}/api/email/admin/unsubscribe`;
            } else {
                throw new Error('Invalid action; must be subscribe or unsubscribe');
            }

            const data = {
                userId: userId,
                type: type
            };

            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json;charset=UTF-8'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }

            const responseData = await response.json();
            this.adminController.log(`Successfully set ${userId} as ${action} from ${type}`);

        } catch (error) {
            this.adminController.error('Error:', error);
            return false;
        }

        return true;
    }

    setLabel($row, label) {
        $row.find('.js-label').text(label);
        return this;
    }

    setStatus($row, status) {
        $row.find('.js-status').removeClass('success danger').addClass(status);
        return this;
    }

    setHint($row, hint) {
        $row.find('.js-label').attr('aria-label', hint);
        return this;
    }

    showSubscribeButton($row) {
        $row
            .find('.js-subscribe-action[data-action=unsubscribe]')
            .addClass('hidden');

        $row
            .find('.js-subscribe-action[data-action=subscribe]')
            .removeClass('hidden');

        return this;
    }

    showUnsubscribeButton($row) {
        $row
            .find('.js-subscribe-action[data-action=unsubscribe]')
            .removeClass('hidden');

        $row
            .find('.js-subscribe-action[data-action=subscribe]')
            .addClass('hidden');

        return this;
    }
}

export default UserTabSubscriptions;
