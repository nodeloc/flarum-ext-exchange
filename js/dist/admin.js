(()=>{var e={n:t=>{var n=t&&t.__esModule?()=>t.default:()=>t;return e.d(n,{a:n}),n},d:(t,n)=>{for(var a in n)e.o(n,a)&&!e.o(t,a)&&Object.defineProperty(t,a,{enumerable:!0,get:n[a]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};(()=>{"use strict";e.r(t);const n=flarum.core.compat["admin/app"];var a=e.n(n);a().initializers.add("nodeloc/flarum-ext-exchange",(function(){a().extensionData.for("nodeloc-exchange").registerSetting({setting:"nodeloc-exchange.api_url",label:a().translator.trans("nodeloc-exchange.admin.settings.api_url"),type:"text"}).registerSetting({setting:"nodeloc-exchange.api_token",label:a().translator.trans("nodeloc-exchange.admin.settings.api_token"),type:"text"}).registerSetting({setting:"nodeloc-exchange.exchange_rate",label:a().translator.trans("nodeloc-exchange.admin.settings.exchange_rate"),type:"number"}).registerPermission({icon:"fas fa-id-card",label:a().translator.trans("nodeloc-exchange.admin.settings.query-others-history"),permission:"exchange.canQueryExchangeHistory",allowGuest:!0},"view")}))})(),module.exports=t})();
//# sourceMappingURL=admin.js.map