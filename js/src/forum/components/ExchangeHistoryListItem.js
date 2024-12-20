import Component from "flarum/Component";
import Link from "flarum/components/Link";
import avatar from "flarum/helpers/avatar";
import username from "flarum/helpers/username";

export default class ExchangeListItem extends Component {
  view() {
    const { userExchangeHistory } = this.attrs;
    const created_at = userExchangeHistory.created_at();
    const money = userExchangeHistory.money();
    const type = userExchangeHistory.type();
    const tx_hash = userExchangeHistory.tx_hash();
    return (
      <tr>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">{created_at}</td>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">{type === 0 ? "能量转积分" : "能量提现"}</td>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">{money}</td>
        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
          <a href={`https://www.oklink.com/zh-hans/polygon/tx/${tx_hash}`} target="_blank">
            {tx_hash}
          </a>
        </td>
      </tr>
    );
  }
}
