import UserPage from 'flarum/forum/components/UserPage';
import ExchangeHistoryList from "./ExchangeHistoryList";

export default class ExchangeHistoryPage extends UserPage {

    oninit(vnode) {
        super.oninit(vnode);

        this.loadUser(m.route.param('username'));
    }

    content() {
      return (
        <div className="Post-body">
          {ExchangeHistoryList.component({
              params: {
                user: this.user,
              },
            })}
          </div>
      );
    }
}
