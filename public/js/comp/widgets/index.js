import inputText from 'es6!./inputText'
import Vue from 'vue'
import selectFriends from 'es6!./selectFriends'
import selectMembers from 'es6!./selectMembers'
import chooseOneComp from 'es6!./chooseOne'

let lastZIndex = 1000

const mask = {
  template: `
  <div style=""
  :style="{zIndex: zIndex}"> <slot></slot> </div>
  `
}

function promote(props) {
  return asyncActionLayer(inputText, props)
}

function selectFromFriends(props) {
  return asyncActionLayer(selectFriends, props)
}

function selectFromMembers(props) {
  return asyncActionLayer(selectMembers, props)
}

function chooseOne(props) {
  return asyncActionLayer(chooseOneComp, props)
}

async function asyncActionLayer(widget, props) {
  return new Promise((resolve, reject) => {
    let zIndex = ++lastZIndex
    let comp = new Vue({
      methods: {
        dismiss () {
          this.$destroy()
          document.body.removeChild(this.$el)
        },
        cancel (reason) {
          this.dismiss()
          reject(reason)
        },
        done (result) {
          resolve(result)
          this.dismiss()
        },
        clickMask (evt) {
          if (evt.target === this.$refs.mask)
            this.cancel('click mask')
        }
      },
      render (h) {
        return h('div', {
          class: 'layer',
          style: {
            zIndex
          },
          on: {
            click: this.clickMask
          },
          ref: 'mask'
        }, [
          h(widget, {
            props: Object.assign({}, {
              promise: {
                resolve: this.done,
                reject: this.cancel
              }
            }, props),
          })
        ])
      }
    })
    comp.$mount()
    document.body.appendChild(comp.$el)
  })
}

export default {
  promote, selectFromFriends, chooseOne,
  selectFromMembers
}