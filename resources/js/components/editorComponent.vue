<template>
    <v-app light>
        <v-container>
            <v-layout row>
            <v-flex md3>
            <form @submit="subirformulario" name="form_file" id="form_file" >
            <p>
                <label for="xmlfile">
                <v-file-input :placeholder="Archivo_valor" ref="fileinput" name="xmlfile" id="xmlfile"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='upload' color="success" dark large form="form_file" >Cargar!</v-btn>
            <!-- @click="snackbar = true" -->
                  <v-snackbar
        v-model="snackbar"
        :multi-line="multiLine"
      >
        {{ text }}
        <v-btn
          color="red"
          text
          @click="snackbar = false"
        >
          Close
        </v-btn>
      </v-snackbar>
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>

            </v-flex>

        </v-layout>
        </v-container>
    </v-app>
</template>
<script>
export default {
    data(){
        return{
            Archivo_valor: 'Archivo...',
            salida: '',
            snackbar:false,
            multiLine: true,
            text: '',
        }
    },
    mounted(){
        console.log("Componente carga archivo montado");
    },
    methods:{
        subirformulario(file){
            file.preventDefault();
            let data = new FormData();
            let currentObj = this;
            let file_new = document.getElementById('form_file');
            data.append('xmlfile',document.getElementById('xmlfile').files[0]);
            let settings = { headers: { 'content-type': 'multipart/form-data' } };

            axios.post('/process',data,settings)
            
            .then(function(response){
                let respuesta = response.data;
                //console.log(respuesta.status);
                currentObj.snackbar = true;
                currentObj.text = respuesta.msg;
                //currentObj.Archivo_valor = "Archivo...";
                //currentObj.$emit('update:value',"null");
                //arreglar esto --------------------------
                //const input = currentObj.$refs.fileinput;
                //input.value = null;
                
            })
            .catch(function(err){
                //currentObj.salida = err;
                currentObj.snackbar = true;
                currentObj.text = err;
                console.log(err);
                
            })
            ;
        },
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
