import Component from "flarum/Component";
import app from "flarum/app";
import LoadingIndicator from "flarum/components/LoadingIndicator";
import Button from "flarum/components/Button";

import ExchangeListItem from "./ExchangeHistoryListItem";
import CreateExchange from "./CreateExchange"
import WithDraw from "./Withdraw"
import Deposit from "./Deposit"
export default class ExchangeList extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.loading = true;
    this.moreResults = false;
    this.userExchangeHistory = [];
    this.user = this.attrs.params.user;
    this.loadResults();
  }

  view() {
    let loading;

    if (this.loading) {
      loading = LoadingIndicator.component({ size: "large" });
    }

    return (
      <div>
        <div style="padding-bottom:10px; font-size: 24px;font-weight: bold;">
          {app.translator.trans("nodeloc-exchange.forum.title")}

        </div>
        <Button
          class="Button Button--primary"
          style="margin-right:10px;"
          onclick={() => {
            app.modal.show(WithDraw, {
              onhide: () => {
                m.route.set(m.route.get());
              }
            });
          }}
        >
          {app.translator.trans('nodeloc-exchange.forum.withdraw')}
        </Button>
{/*
       <Button
          class="Button Button--primary"
          style="margin-right:10px;"
          onclick={() => {
            app.modal.show(Deposit, {
              onhide: () => {
                m.route.set(m.route.get());
              }
            });
          }}
        >
          {app.translator.trans('nodeloc-exchange.forum.deposit')}
        </Button> */}

        <Button
          class="Button Button--primary"
          onclick={() => {
            app.modal.show(CreateExchange, {
              onhide: () => {
                m.route.set(m.route.get());
              }
            });
          }}
        >
          {app.translator.trans('nodeloc-exchange.forum.create_exchange')}
        </Button>
        <ul style="margin: 0;padding: 0;list-style-type: none;position: relative;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                      <tr>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">{app.translator.trans('nodeloc-exchange.forum.record.date')}</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">{app.translator.trans('nodeloc-exchange.forum.record.type')}</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">{app.translator.trans('nodeloc-exchange.forum.record.money-out')}</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd;">{app.translator.trans('nodeloc-exchange.forum.record.tx-link')}</th>
                      </tr>
                    </thead>
                    <tbody>
                    {this.userExchangeHistory.map((userExchangeHistory) => {
                      return (
                        <>
                          {ExchangeListItem.component({ userExchangeHistory })}
                        </>
                      );
                    })}
                    </tbody>
          </table>
        </ul>

        {!this.loading && this.userExchangeHistory.length===0 && (
          <div>
            <div style="font-size:1.4em;color: var(--muted-more-color);text-align: center;height: 300px;line-height: 100px;">{app.translator.trans("nodeloc-exchange.forum.list-empty")}</div>
          </div>
        )}

        {this.hasMoreResults() && (
          <div style="text-align:center;padding:20px">
            <Button className={'Button Button--primary'} disabled={this.loading} loading={this.loading} onclick={() => this.loadMore()}>
              {app.translator.trans('nodeloc-exchange.forum.money-list-load-more')}
            </Button>
          </div>
        )}
      </div>
    );
  }

  loadMore() {
    this.loading = true;
    this.loadResults(this.userExchangeHistory.length);
  }

  parseResults(results) {
    this.moreResults = !!results.payload.links && !!results.payload.links.next;
    [].push.apply(this.userExchangeHistory, results);
    this.loading = false;
    m.redraw();

    return results;
  }

  hasMoreResults() {
    return this.moreResults;
  }

  loadResults(offset = 0) {
    let url = '/users/' + this.user.id() + '/exchange';
    return app.store
      .find(url, {
        filter: {
          user: this.user.id(),
        },
        page: {
          offset,
        },
      })
      .catch(() => {})
      .then(this.parseResults.bind(this));
  }
}
