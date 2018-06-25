import Vue from 'vue'

const vue_fb = {}
vue_fb.install = function install(Vue, options) {
    (function(d, s, id){
        var js, fjs = typeof document !== 'undefined' ? d.getElementsByTagName(s)[0] : null
        if (typeof document === 'undefined'  || d.getElementById(id)) {return}
        js = d.createElement(s)
        js.id = id
        js.src = "//connect.facebook.net/en_US/sdk.js"
        fjs.parentNode.insertBefore(js, fjs)
    }(typeof document !== 'undefined' ? document : null, 'script', 'facebook-jssdk'))

    if (typeof window !== 'undefined') {
        window.fbAsyncInit = function onSDKInit() {
            FB.init(options)
            FB.AppEvents.logPageView()
            Vue.FB = FB
            window.dispatchEvent(new Event('fb-sdk-ready'))
        }
    }

    Vue.FB = undefined
}

Vue.use(vue_fb, {
    appId: FACEBOOK_APPID,
    autoLogAppEvents: true,
    version: 'v3.0'
})