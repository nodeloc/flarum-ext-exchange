import app from 'flarum/admin/app';

app.initializers.add('nodeloc/flarum-ext-exchange', () => {
  app.extensionData.for("nodeloc-exchange")
    .registerSetting({
      setting: 'nodeloc-exchange.api_url',
      label: app.translator.trans('nodeloc-exchange.admin.settings.api_url'),
      type: 'text',
    })
    .registerSetting({
      setting: 'nodeloc-exchange.api_token',
      label: app.translator.trans('nodeloc-exchange.admin.settings.api_token'),
      type: 'text',
    })
    .registerSetting({
      setting: 'nodeloc-exchange.exchange_rate',
      label: app.translator.trans('nodeloc-exchange.admin.settings.exchange_rate'),
      type: 'number',
    })
    .registerSetting({
      setting: 'nodeloc-exchange.blockchain_api_url',
      label: app.translator.trans('nodeloc-exchange.admin.settings.blockchain_api_url'),
      type: 'text',
    })
    .registerSetting({
      setting: 'nodeloc-exchange.blockchain_token',
      label: app.translator.trans('nodeloc-exchange.admin.settings.blockchain_token'),
      type: 'text',
    })
    .registerSetting({
      setting: 'nodeloc-exchange.blockchain_min',
      label: app.translator.trans('nodeloc-exchange.admin.settings.blockchain_min'),
      type: 'number',
    })
    .registerPermission(
      {
        icon: 'fas fa-id-card',
        label: app.translator.trans('nodeloc-exchange.admin.settings.query-others-history'),
        permission: 'exchange.canQueryExchangeHistory',
        allowGuest: true
      }, 'view')
    .registerPermission(
      {
        icon: 'fas fa-id-card',
        label: app.translator.trans('nodeloc-exchange.admin.settings.canExchange'),
        permission: 'exchange.canExchange',
        allowGuest: false
      }, 'view')
});
