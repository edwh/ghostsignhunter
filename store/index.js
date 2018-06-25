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

            setMapModalOpen(state, open, item) {
                state.mapModalItem = item;
                state.mapModalOpen = open;
            }
        }
    })
}

export default createStore