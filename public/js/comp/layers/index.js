import login from 'es6!./login'
import qrcode from 'es6!./qrcode'
// import 'es6!./login'
// import 'es6!./login'
const template =
  `
<div class="layer"
  v-show="isShow">
  <login v-show="$state.layers.login.show"></login>
  <qrcode v-show="$state.layers.qrcode.show"></qrcode>
</div>
`

export default {
  template,
  components: {
    login,
    qrcode,
  },
  computed: {
    isShow () {
      let layers = this.$state.layers
      return layers.login.show
        || layers.qrcode.show
    }
  }
}
