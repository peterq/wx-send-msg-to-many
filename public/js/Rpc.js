export default class Rpc {

  constructor (url) {
    this.url = url
    this.eventListener = {}
    this.requestMap = {}
    this.init()
  }

  init () {

    this.openPromise = this.connect()

    this.addEventListener('session.new', function (data) {
      localStorage.setItem('rpc_session', data);
    })

    setInterval(_ => this.call('system.ping'), 3e3)
  }

  connect() {
    return new Promise((resolve, reject) => {
      var ws  = this.ws = new WebSocket(this.url);
      ws.onopen = async evt => {
        let sessionId = localStorage.getItem('rpc_session')
        if (sessionId) {
          await this.call('system.resume', {sessionId})
        }
        resolve(evt)

        this.onopen(evt)
      };
      ws.onerror = evt => this.onerror(evt);
      ws.onmessage = evt => this.onmessage(evt);
      ws.onclose = evt => this.onclose(evt);
    })
  }

  reconnnect () {
    this.ws.close()
    return this.connect()
  }


  onerror (evt) {
    console.log(evt)
    let cbs = this.eventListener['rpc.error'] || []
    cbs.forEach(function (cb) {
      setTimeout(function () {
        cb('rpc.error')
      })
    })
  }

  onclose (evt) {
    console.log(evt)
    let cbs = this.eventListener['rpc.closed'] || []
    cbs.forEach(function (cb) {
      setTimeout(function () {
        cb('rpc.closed')
      })
    })
  }

  onopen () {

  }

  onmessage (evt) {
    var data = JSON.parse(evt.data)
    if (data.type == 'event') {
      let cbs = this.eventListener[data.event] || []
      cbs.forEach(function (cb) {
        setTimeout(function () {
          cb(data.data, data.event)
        })
      })
      return
    }
    if (data.type == 'result') {
      let reqid = data.reqid
      this.requestMap[reqid].resolve(data.result)
      delete this.requestMap[reqid]
      return
    }
    if (data.type == 'error') {
      let reqid =  data.reqid
      if (reqid) this.requestMap[reqid].reject(data.error)
      else console.error(data)
      delete this.requestMap[reqid]
      return
    }
  }

  addEventListener (name, cb) {
    this.eventListener[name] = this.eventListener[name] || []
    this.eventListener[name].push(cb)
  }

  on (name, cb) {
    return this.addEventListener(name, cb)
  }

  getSessionId () {
    return localStorage.getItem('rpc_session')
  }

  call (func, param = {}) {
    var reqid = 'cb' + new Date().getTime() + ~~(Math.random() * 1e5)
    let done = {}
    let promise = new Promise(function (resolve, reject) {
      done.resolve = resolve
      done.reject = reject
    })
    this.requestMap[reqid] = done
    this.ws.send(JSON.stringify({
      func,
      param,
      reqid
    }))
    return promise
  }

}