import Model from 'flarum/common/Model';

export default class UserExchangeHistory extends Model {}
Object.assign(UserExchangeHistory.prototype, {
  money : Model.attribute('money'),
  type : Model.attribute('type'),
  tx_hash : Model.attribute('tx_hash'),
  created_at : Model.attribute('created_at'),
})
