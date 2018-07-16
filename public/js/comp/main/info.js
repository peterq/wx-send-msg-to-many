const template = `
<el-card>
  <div slot="header">
   联系信息
  </div> 
  <div class="contacts-card">
    好友 {{Object.keys($state.wxData.contacts.friends).length}} 人
  </div>
  <div class="contacts-card">
    群聊 {{Object.keys($state.wxData.contacts.groups).length}} 个
  </div>
   <div class="contacts-card">
    群友 {{Object.keys($state.wxData.contacts.members).length}} 人
  </div>
  <div class="contacts-card">
    公众号 {{Object.keys($state.wxData.contacts.officials).length}} 个
  </div>
</el-card>
`

export default {
  template
}
