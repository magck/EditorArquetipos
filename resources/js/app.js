import Vue from 'vue';
import axios from 'axios';
import Vuetify from 'vuetify';
window.Vue = require('vue');
window.axios = axios;
import "../../node_modules/vuetify/dist/vuetify.min.css"
Vue.use(Vuetify);
//Vue.config.productionTip = false
Vue.component('example-component', require('./components/barra_navComponent.vue').default);
Vue.component('homepage-component', require('./components/homepageComponent.vue').default);
Vue.component('editor-component', require('./components/editorComponent.vue').default);
const app = new Vue({
    el: '#app',
    vuetify: new Vuetify(),
})
  
