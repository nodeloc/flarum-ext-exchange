import Model from 'flarum/common/Model';

export default class UserExchangeHistory extends Model {}
Object.assign(UserExchangeHistory.prototype, {
  money : Model.attribute('money'),
  credits : Model.attribute('credits'),
  created_at : Model.attribute('created_at'),
})
