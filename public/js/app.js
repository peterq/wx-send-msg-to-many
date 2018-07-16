import  Vue from "vue"
import app from './comp/app'
import ElementUI from 'ELEMENT'
import State from "./comp/state"
import scheduler from "es6!scheduler"
import Rpc from "es6!./Rpc"

Vue.use(ElementUI)

Vue.mixin({
  created: function () {
    this.$state = this.$options.state || (this.$parent && this.$parent.$state) || {}
    this.$event = this.$options.event || (this.$parent && this.$parent.$event) || window.$event || {}
    this.$rpc = this.$options.rpc || (this.$parent && this.$parent.$rpc) || {}
  }
})
window.Vue = Vue
const event = (function () {
  function e(e, t) {
    console.log('event', e, t)
    n[e] && n[e].map(function (e) {
       setTimeout(_ => e(t), 0)
    })
  }
  function t(e, t) {
    n[e] || (n[e] = []), n[e].push(t)
  }
  var n = {};
  return {fire: e, on: t}
})()
window.$event = event
const rpc = new Rpc((location.protocol == 'https:' ? 'wss' : 'ws') + '://' + location.host)
const state = new Vue(State)
scheduler(event, rpc, state)
window.app = new Vue({
  state,
  event,
  rpc,
  components: {
    app
  },
  template: '<app/>'
}).$mount('#app')
