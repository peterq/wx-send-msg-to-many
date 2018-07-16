

export default function (event, rpc, state) {
  // 更新微信状态
  async function checkWxStatus() {
    let status = await rpc.call('wxStatus')
    state.wxData.status = status
  }
  async function checkWxStatusOnInit() {
    let status = await rpc.call('wxStatus')
    state.wxData.status = status
    if (status == 'waiting-scan') {
      let qrcode = await rpc.call('qrcode')
      state.wxData.qrcode = qrcode
    }
  }
  // 创建客户端
  async function createClient() {
    await rpc.call('newClient')
    await checkWxStatus()
  }

  async function getContacts() {
    let contacts = await rpc.call('contacts')
    state.wxData.contacts = processContacts(contacts)
  }

  async function getTaskList() {
    let tasks = await rpc.call('taskList')
    state.tasks = Object.values(tasks)
  }

  // 处理通讯录
  function processContacts(contacts) {
    let {friends, groups, members} = contacts || state.wxData.contacts
    // 好友处理
    for (let username in friends) {
      let f = friends[username]
      f.showLabel = f.RemarkName ? f.RemarkName + '('+ f.NickName +')' : f.NickName
      f.label = f.RemarkName + f.NickName + f.PYInitial +
        f.PYQuanPin + f.RemarkPYInitial + f.RemarkPYQuanPin
      f.key = username
    }
    // 群员处理
    for (let groupId in groups) {
      groups[groupId].showLabel = groups[groupId].NickName
      for (let member of groups[groupId].MemberList) {
        if (member.UserName in friends) {
          Object.assign(member, friends[member.UserName])
        } else {
          member.showLabel = member.NickName + (member.DisplayName ? `(${member.DisplayName})` : '')
          member.key = member.UserName
          member.disabled = true
          member.label = member.NickName + member.DisplayName + member.KeyWord
        }
      }
    }
    return contacts
  }
  rpc.on('wx.data.change', ({key, value}) => state.wxData[key] = value)
  event.on('login-success', checkWxStatusOnInit)
  event.on('state.wxData.status.not-created', createClient)
  event.on('state.wxData.status.not-created', createClient)
  event.on('state.wxData.status.ok', old => !old && getContacts())
  event.on('state.wxData.contacts', processContacts)
  rpc.on('wx.qrcode', qrcode => state.wxData.qrcode = qrcode)
  rpc.on('wx.filehelper.message', message => state.filehelperMessages.push(message))
  rpc.on('task.sending', data => {
    event.fire('task.sending.' + data.taskId, data)
  })
  rpc.on('task.sent', data => {
    event.fire('task.sent.' + data.taskId, data)
  })
  rpc.on('task.created', task => {
    state.tasks.push(task)
  })
  rpc.on('client.exit', _ => {alert('微信客户端异常退出, 请刷新页面重试')})
  rpc.on('rpc.closed', _ => {
    state.appStatus = '已断开, 正在重连'
    setTimeout(_ => rpc.reconnnect().then(state.appStatus = '已连接'), 5e3)
  })

  // 初始化
  ;(async function () {
    rpc.addEventListener('session.moved', function (data) {
      alert('已在其他地方登录, 此处自动下线')
      state.appStatus = '已下线'
    })
    await rpc.openPromise
    state.appStatus = '已连接'
    var username = await rpc.call('username')
    if (!username) {
      event.fire('not-login')
    }
    else state.username = username

    // 查询任务列表
    getTaskList()

  })()

}
