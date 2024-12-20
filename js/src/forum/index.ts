import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserPage from 'flarum/forum/components/UserPage';
import LinkButton from 'flarum/common/components/LinkButton';
import ExchangeHistoryPage from './components/ExchangeHistoryPage';
import userExchangeHistory from "./models/UserExchangeHistory";

app.initializers.add('flarum-ext-exchange', () => {
  app.store.models.userExchangeHistory = userExchangeHistory;

  app.routes.userExchangeHistory = {
    path: '/u/:username/exchange',
    component: ExchangeHistoryPage,
  };

  extend(UserPage.prototype, 'navItems', function (items) {
    if (app.session.user && app.forum.attribute('canExchange')===true) {
      if (app.session.user.id() == this.user.id() || !app.session.user.attribute('canQueryExchange') ) {
        items.add('userExchangeHistory', LinkButton.component({
          href: app.route('userExchangeHistory', {
            username: this.user.slug(),
          }),
          icon: 'fa-solid fa-recycle',
        }, app.translator.trans('nodeloc-exchange.forum.nav')));
      }
    }

  });
});
