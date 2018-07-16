const tpl =
  `
<div style="height: 100%; color: white; align-items: center" class="flex">
<div v-if="$state.username">{{$state.username}}</div>
<div v-else>未登录</div>
<div style="flex: 1; text-align: center;"><h3>微信群发系统</h3></div>
<div>{{$state.appStatus}} <el-button @click.native="exit" size="mini" v-show="$state.wxData.status && $state.wxData.status != 'not-created'">退出服务器微信</el-button></div>
</div>
`

export default {
  created () {
    // console.log(this)
  },
  template: tpl,
  methods: {
    exit () {
      this.$rpc.call('destroyClient')
    }
  }
}