import Vuex from 'vuex'

const createStore = () => {
    return new Vuex.Store({
        state: {
            signs: [],
            facebook: null,
            loggedIn: false,
            centre: null,
            zoom: null
        },
        mutations: {
            setSigns (state, signs) {
                state.signs = signs;
            },

            setMapModalOpen(state, open, item) {
                state.mapModalItem = item;
                state.mapModalOpen = open;
            },

            setFacebook(state, data) {
                state.facebook = data;
                state.loggedIn = true;
            },

            clearFacebook(state) {
                state.facebook = null;
                state.loggedIn = false;
            },

            setZoom(state, zoom) {
                state.zoom = zoom;
            },

            setCentre(state, [ lat, lng ]) {
                state.centre = {
                    lat: lat,
                    lng: lng
                }
            },

            setNews(state, news) {
                state.news = news;
            }
        }
    })
}

export default createStore