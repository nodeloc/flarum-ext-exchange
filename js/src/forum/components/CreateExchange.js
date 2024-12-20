import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import Alert from 'flarum/common/components/Alert';

export default class CreateExchange extends Modal {
  constructor() {
    super();
    this.exchange_rate = app.forum.attribute('exchange_rate');
    this.credits = Stream('0');
    this.loading = false;
  }

  className() {
    return 'create-exchange Modal--small';
  }

  title() {
    return app.translator.trans('nodeloc-exchange.forum.create_exchange');
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/exchange',
      body: {
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
      app.alerts.show(Alert, { type: 'success' }, "能量转换成功!");
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
              for="buy-store-to-mail">{app.translator.trans('nodeloc-exchange.forum.input_help')}</label>
            <div
              className="helpText">{app.translator.trans('nodeloc-exchange.forum.exchange_rate', {exchange_rate: this.exchange_rate})} </div>
            <input
              required
              id="buy-store-to-mail"
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
            转换
          </Button>
        </div>
      </div>
    );
  }
}
