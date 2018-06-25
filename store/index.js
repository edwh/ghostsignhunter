import Vuex from 'vuex'

const createStore = () => {
    return new Vuex.Store({
        state: {
            signs: [],
            facebook: null,
            loggedIn: false
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
            }
        }
    })
}

export default createStore