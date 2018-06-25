import Vue from 'vue'
import VueAxios from 'vue-axios'
import VueAuthenticate from 'vue-authenticate'
import axios from 'axios';

Vue.use(VueAxios, axios)
Vue.use(VueAuthenticate, {
    baseUrl: 'https://www.ghostsignhunter.org',
    providers: {
        facebook: {
            clientId: FACEBOOK_APPID,
            name: 'facebook',
            url: '/auth_facebook.php',
            authorizationEndpoint: 'https://www.facebook.com/v3.0/dialog/oauth',
            redirectUri: getRedirectUri('/'),
            requiredUrlParams: ['display', 'scope'],
            scope: ['email', 'public_profile'],
            scopeDelimiter: ',',
            display: 'popup',
            oauthType: '2.0',
            popupOptions: { width: 580, height: 400 }
        },
    }
})

function getRedirectUri(uri) {
    try {
        console.log("App id", FACEBOOK_APPID);
        console.log("Get redirect uri", SITE, uri);
        return(SITE + uri);
    } catch (e) {}

    return uri || null;
}
