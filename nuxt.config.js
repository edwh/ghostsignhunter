module.exports = {
    head: {
        title: 'Ghost Sign Hunter',
        meta: [
            {charset: 'utf-8'},
            {name: 'viewport', content: 'width=device-width, initial-scale=1'},
            {hid: 'description', content: 'Hunt those ghosts!'}
        ],
        link: [
            {rel: 'icon', type: 'image/x-icon', href: 'favicon.ico'}
        ]
    },
    css: [],
    router: {
        linkActiveClass: 'selected'
    },
    modules: [
        'bootstrap-vue/nuxt',
    ],
    plugins: [
        '~/plugins/vue-googlemaps'
    ],
    build: {
        extend (config, {isDev, isClient}) {
            if (!isClient) {
                // This instructs Webpack to include `vue2-google-maps`'s Vue files
                // for server-side rendering
                config.externals.splice(0, 0, function (context, request, callback) {
                    if (/^vue2-google-maps($|\/)/.test(request)) {
                        callback(null, false)
                    } else {
                        callback()
                    }
                })
            }
        }
    }
}
