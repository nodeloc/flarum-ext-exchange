import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Alert from 'flarum/common/components/Alert';

export default class WithDraw extends Modal {
  constructor() {
    super();
    this.exchange_rate = app.forum.attribute('exchange_rate');
    this.credits = Stream('0');
    this.withdraw_address = Stream('');
    this.loading = false;
  }

  className() {
    return 'create-exchange Modal--small';
  }

  title() {
    return app.translator.trans('nodeloc-exchange.forum.withdraw');
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/withdraw',
      body: {
        withdraw_address: this.withdraw_address(),
        credits: this.credits(),
      },
    }).then((result) => {
      this.loading = false;
      if (result.error) {
        app.alerts.show(
          {
            type: 'error',
          },
          result.error
        );
        return;
      }
      app.alerts.show(Alert, { type: 'success' }, "提现成功!");
      // Close the purchase box
      this.hide();
    });
  }
  onhide() {
    super.onhide();
    m.redraw();
  }
  content() {
    return (
      <div className="container buy-store-layer">
        <div className="Form">
          <div className="Form-group">
            <label
              for="withdraw">{app.translator.trans('nodeloc-exchange.forum.withdraw_address')}</label>
            <input
              required
              id="withdraw"
              className="FormControl"
              type="text"
              bidi={this.withdraw_address}
            />
            <label
              for="withdraw">{app.translator.trans('nodeloc-exchange.forum.withdraw_amount')}</label>
            <input
              required
              id="withdraw"
              className="FormControl"
              type="number"
              bidi={this.credits}
            />
          </div>
          <Button
            className="Button Button--primary"
            type="submit"
            loading={this.loading}
            onclick={(e) => this.onsubmit(e)}
          >
            {app.translator.trans('nodeloc-exchange.forum.withdraw')}
          </Button>
        </div>
      </div>
    );
  }
}
