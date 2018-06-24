import Vue from 'vue'
import * as VueGoogleMaps from 'vue2-google-maps'
import GmapCluster from 'vue2-google-maps/dist/components/cluster'

Vue.use(VueGoogleMaps, {
    load: {
        key: 'AIzaSyBVEwwZ9dBpRLXMcdJv1LDrAV-JY-F6kzI',
        libraries: ['places'],
        useBetaRenderer: false,
    },
})

Vue.component('GmapCluster', GmapCluster)