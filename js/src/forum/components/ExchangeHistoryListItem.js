import Component from "flarum/Component";
import Link from "flarum/components/Link";
import avatar from "flarum/helpers/avatar";
import username from "flarum/helpers/username";

export default class ExchangeListItem extends Component {
  view() {
    const {userExchangeHistory} = this.attrs;
    const created_at = userExchangeHistory.created_at();
    const money = userExchangeHistory.money();
    const credits = userExchangeHistory.credits();

    return (
      <div className="transferHistoryContainer">
        <div style="padding-top: 5px;">
          <b>{created_at}</b>&nbsp;|&nbsp;
          <b>{app.translator.trans('nodeloc-exchange.forum.record.money-out')}: </b>
          {money}&nbsp;|&nbsp;
          <b>{app.translator.trans('nodeloc-exchange.forum.record.money-in')}: </b>
          {credits}
        </div>
      </div>
    );
  }
}
