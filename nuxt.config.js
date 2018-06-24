module.exports = {
    head: {
        title: 'Ghost Sign Hunter',
        meta: [
            { charset: 'utf-8' },
            { name: 'viewport', content: 'width=device-width, initial-scale=1' },
            { hid: 'description', content: 'Hunt those ghosts!' }
        ],
        link: [
            { rel: 'icon', type: 'image/x-icon', href: 'favicon.ico' }
        ]
    },
    css: [
    ],
    router: {
        linkActiveClass: 'selected'
    },
    modules: [
        'bootstrap-vue/nuxt'
    ]
}
