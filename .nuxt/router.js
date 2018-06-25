import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

const _007961f8 = () => import('..\\pages\\terms.vue' /* webpackChunkName: "pages_terms" */).then(m => m.default || m)
const _7659f652 = () => import('..\\pages\\add.vue' /* webpackChunkName: "pages_add" */).then(m => m.default || m)
const _22c33d8e = () => import('..\\pages\\privacy.vue' /* webpackChunkName: "pages_privacy" */).then(m => m.default || m)
const _567ba3c3 = () => import('..\\pages\\index.vue' /* webpackChunkName: "pages_index" */).then(m => m.default || m)



if (process.client) {
  window.history.scrollRestoration = 'manual'
}
const scrollBehavior = function (to, from, savedPosition) {
  // if the returned position is falsy or an empty object,
  // will retain current scroll position.
  let position = false

  // if no children detected
  if (to.matched.length < 2) {
    // scroll to the top of the page
    position = { x: 0, y: 0 }
  } else if (to.matched.some((r) => r.components.default.options.scrollToTop)) {
    // if one of the children has scrollToTop option set to true
    position = { x: 0, y: 0 }
  }

  // savedPosition is only available for popstate navigations (back button)
  if (savedPosition) {
    position = savedPosition
  }

  return new Promise(resolve => {
    // wait for the out transition to complete (if necessary)
    window.$nuxt.$once('triggerScroll', () => {
      // coords will be used if no selector is provided,
      // or if the selector didn't match any element.
      if (to.hash) {
        let hash = to.hash
        // CSS.escape() is not supported with IE and Edge.
        if (typeof window.CSS !== 'undefined' && typeof window.CSS.escape !== 'undefined') {
          hash = '#' + window.CSS.escape(hash.substr(1))
        }
        try {
          if (document.querySelector(hash)) {
            // scroll to anchor by returning the selector
            position = { selector: hash }
          }
        } catch (e) {
          console.warn('Failed to save scroll position. Please add CSS.escape() polyfill (https://github.com/mathiasbynens/CSS.escape).')
        }
      }
      resolve(position)
    })
  })
}


export function createRouter () {
  return new Router({
    mode: 'history',
    base: '/',
    linkActiveClass: 'selected',
    linkExactActiveClass: 'nuxt-link-exact-active',
    scrollBehavior,
    routes: [
		{
			path: "/terms",
			component: _007961f8,
			name: "terms"
		},
		{
			path: "/add",
			component: _7659f652,
			name: "add"
		},
		{
			path: "/privacy",
			component: _22c33d8e,
			name: "privacy"
		},
		{
			path: "/",
			component: _567ba3c3,
			name: "index"
		}
    ],
    
    
    fallback: false
  })
}
