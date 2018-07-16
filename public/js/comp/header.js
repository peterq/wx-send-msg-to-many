const tpl =
  `
<div style="height: 100%; color: white; align-items: center" class="flex">
<div v-if="$state.username">{{$state.username}}</div>
<div v-else>未登录</div>
<div style="flex: 1; text-align: center;"><h3>微信群发系统</h3></div>
<div>{{$state.appStatus}}</div>
</div>
`

export default {
  created () {
    // console.log(this)
  },
  template: tpl
}