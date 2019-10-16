<template>
    <v-app light>
        <v-container>
            <v-layout row>
            <v-flex md3>
            <form @submit="subirformulario" name="form_file" id="form_file" >
            <v-subheader>Cargar Arquetipo desde PC</v-subheader>
            <p>
                <label for="xmlfile">
                <v-file-input :placeholder="Archivo_valor" name="xmlfile" id="xmlfile"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='upload' color="success" dark large form="form_file" >Cargar</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            <v-subheader>Guardar en MongoDB </v-subheader>
            <!--Formulario subir archivo y guardarlo en mongo DB-->
            <form @submit="guardarDB" name="mongo_save" id="mongo_save">
            <p>
                <label for="xmlsave">
                    <v-file-input :placeholder="Archivo_valor" name="xmlsave" id="xmlsave"></v-file-input>
                </label>
            </p>
                <v-btn type="submit" id='guardar' color="success" dark large form="mongo_save">Guardar</v-btn>
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            <!-- -->
            <!--FORMULARIO PARA SUBIR UN ARQUETIPO DESDE EL PC Y PROCESARLO  -->
            <!-- -->
            <form @submit="procesarArquetipo" name="form_file_load" id="form_file_load" >
            <v-subheader>Cargar Arquetipo desde PC</v-subheader>
            <p>
                <label for="xmlfile_load">
                <v-file-input :placeholder="Archivo_valor" name="xmlfile_load" id="xmlfile_load"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='procesa' color="success" dark large form="form_file_load">Procesar</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="red" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>
                <div id="jsmind_container">
                </div>
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
            nodo: {"id":"root","parentid":"","isroot":true,"topic":"jsMind","background-color":"#0000ff","direction":""}
        }
    },
    mounted(){


        // jm.set_readonly(true);
        // var mind_data = jm.get_data();
        // alert(mind_data);
        //jm.add_node("sub2","sub23", "new node", {"background-color":"red"});
        //jm.set_node_color('sub21', 'green', '#ccc');
    },
    methods:{
        subirformulario(file){
            file.preventDefault();
            let data = new FormData();
            let currentObj = this;
            //let file_new = document.getElementById('form_file');
            data.append('xmlfile',document.getElementById('xmlfile').files[0]);
            let settings = { headers: { 'content-type': 'multipart/form-data' } };

            axios.post('/process',data,settings)
            .then(function(response){
                let respuesta = response.data;
                console.log(respuesta);
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
        guardarDB(formulario){
            formulario.preventDefault();
            let data = new FormData();
            let currentObj = this;
            let file_form = document.getElementById('xmlsave');
            let settings = { headers: { 'content-type': 'multipart/form-data' } };
            data.append('xmlsave',file_form.files[0]);
            data.append('nombre',file_form.files[0].name);
            data.append('extension','xml');
            axios.post('/insertar',data,settings)
            .then(function(response){
                let rspta = response.data;
                currentObj.snackbar = true;
                currentObj.text = rspta.msg;
            })
            .catch(function(error){
                console.log(error.message);
            })
        },
        procesarArquetipo(formulario){
            formulario.preventDefault();
            let data = new FormData();
            let currentObj = this;
            let archv_formulario = document.getElementById('xmlfile_load');
            let settings = { headers: { 'content-type': 'multipart/form-data' } };
            data.append('archivo_xml',archv_formulario.files[0]);
            data.append('nombre_xml',archv_formulario.files[0].name);
            axios.post('/procesar_xml',data,settings)
            .then(function (response) {
                let rspta = response.data;
                currentObj.snackbar = true;
                currentObj.text = rspta.msg;
                let nodos_jsmind = rspta.nodos
                var mind = {
                    "meta":{
                        "name":"archetype",
                        "author":"editor_importe",
                        "version":"0.1",
                    },
                    "format":"node_array",
                    "data":[nodos_jsmind]
                };
                var options = {
                    container:'jsmind_container',
                    editable:true,
                    theme:'primary'
                }
                var jm = jsMind.show(options,mind);
                console.log(rspta.nodos);
            })
            .catch(function (error) {
                console.log(error.message);
                console.log(error.response.data);
                console.log(error.response.status);
                console.log(error.response.headers);
            })            

        },
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
