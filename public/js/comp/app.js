import state from '../comp/state'
import header from '../comp/header'
import layers from '../comp/layers/index'
import wxMain from '../comp/main/index'
const tpl = `
<el-container>
  <el-header style="background-color: #7f8dff"><app-header></app-header></el-header>
  <el-main>
    <wx-main></wx-main>
  </el-main>
  <layers></layers>
</el-container>
`

export default {
  async created() {
    this.$event.on('not-login', _ => this.$state.layers.login.show = true)
    this.$event.on('state.wxData.qrcode',  _ => this.$state.layers.qrcode.show = true)
    this.$event.on('state.wxData.status.login_success', _ => this.$state.layers.qrcode.show = false)
    this.$event.on('state.wxData.status.ok', _ => this.$state.layers.qrcode.show = false)
    this.$event.on('state.wxData.status.ok', _ => this.$message.success('微信账号登录成功'))
  },
  data() {
    return {
    }
  },
  methods: {
  },
  template: tpl,
  components: {
    state,
    appHeader: header,
    layers,
    wxMain
  }
}
