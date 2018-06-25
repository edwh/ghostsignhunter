import Vuex from 'vuex'

const createStore = () => {
    return new Vuex.Store({
        state: {
            signs: []
        },
        mutations: {
            setSigns (state, signs) {
                state.signs = signs;
            },

            setMapModalOpen(state, open) {
                state.mapModalOpen = open;
            },

            setMapModalItem(state, item) {
                state.mapModalItem = item;
            }
        }
    })
}

export default createStore