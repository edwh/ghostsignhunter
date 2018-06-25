const webpack = require('webpack')

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
        '@nuxtjs/axios',
    ],
    plugins: [
        '~/plugins/vue-googlemaps',
        '~/plugins/fb-sdk',
        { src: '~/plugins/localStorage.js', ssr: false }
    ],

    css: [
        '@/assets/css/style.css'
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

            const vueLoader = config.module.rules.find((rule) => rule.loader === 'vue-loader')
            vueLoader.options.transformToRequire = {
                'img': 'src',
                'image': 'xlink:href',
                'b-img': 'src',
                'b-img-lazy': ['src', 'blank-src'],
                'b-card': 'img-src',
                'b-card-img': 'img-src',
                'b-carousel-slide': 'img-src',
                'b-embed': 'src'
            }
        },
        plugins: [
            new webpack.DefinePlugin({
                'API' : "'https://www.ghostsignhunter.org/api/'",
                'FACEBOOK_APPID' : '1917283041629729',
                'SITE' : "'https://www.ghostsignhunter.org'"
            }),
            new webpack.ProvidePlugin({
                '_': 'lodash'
            })
        ]
    },

    axios: {
        proxy: true
    },

    proxy: [
        'https://www.ghostsignhunter.org/api'
    ],

    generate: {
        minify: {
            collapseWhitespace: false
        }
    }
}
