import Vuex from 'vuex'

const createStore = () => {
    return new Vuex.Store({
        state: {
            signs: [],
            facebook: null
        },
        mutations: {
            setSigns (state, signs) {
                state.signs = signs;
            },

            setMapModalOpen(state, open, item) {
                state.mapModalItem = item;
                state.mapModalOpen = open;
            },

            setFacebook(data) {
                state.facebook = data;
            }
        }
    })
}

export default createStore