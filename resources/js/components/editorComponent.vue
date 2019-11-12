<template>
    <v-app light>
        <v-container>
            <v-layout row>
            <v-flex md3>
            <form @submit="subirformulario" name="form_file" id="form_file" >
            <v-subheader>Guardar Arquetipo en PC</v-subheader>
            <p>
                <label for="xmlfile">
                <v-file-input :placeholder="Archivo_valor" name="xmlfile" id="xmlfile"></v-file-input>
                </label>
            </p>
            <v-btn type="submit" id='upload' color="success" dark large form="form_file" >Guardar</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
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
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
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
            <v-btn color="success" dark large v-on:click="abrirMindmap">Abrir jsMind Mindmap</v-btn>
            <!-- @click="snackbar = true" -->
            <v-snackbar v-model="snackbar" :multi-line="multiLine"> 
                {{ text }} 
                <v-btn color="green" text @click="snackbar = false">Close</v-btn>
            </v-snackbar>
            </form>
            </v-flex>
            <v-flex md1>
            </v-flex>
            <v-flex md8>
                <div id="main"></div>
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
            data: '',
            nombre_arch_expor: '',
            arquetipos_actuales: [],
        }
    },
/*      mounted(){
    var mind = ;
    var options = {
        container:'main',
        editable:true,
        theme:'primary'
    }
    console.log(mind);
    var jm = jsMind.show(options,mind);
    }, */

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
            currentObj.nombre_arch_expor = archv_formulario.files[0].name
            data.append('archivo_xml',archv_formulario.files[0]);
            data.append('nombre_xml',archv_formulario.files[0].name);
            axios.post('/procesar_xml',data,settings)
            .then(function (response) {
                let rspta = response.data;
                currentObj.snackbar = true;
                currentObj.text = rspta.msg;
                let padre_jsmind = rspta.padre;
                let nombre_archetype = rspta.nombre_archetype;
                console.log(padre_jsmind);                
                var mind = JSON.parse(padre_jsmind);
                var id_container = currentObj.crearDiv();
                var options = {
                    container:id_container,
                    editable:true,
                    theme:'primary'
                }

                var jm = jsMind.show(options,mind);
                jm.collapse_all();
                currentObj.data = jm
                var obj = {};
                obj[id_container] = jm;
                currentObj.arquetipos_actuales.push(obj);

            })
            .catch(function (error) {
                //let mensaje_error = error.response.data.msg;
                //currentObj.snackbar = true;
                //currentObj.text = mensaje_error;
                if(error.response.status == 422){
                    var mensaje_error = error.response.data.message;
                    currentObj.snackbar = true;
                    currentObj.text = mensaje_error;
                }else{
                    var mensaje_error = error.response.data.msg;
                    currentObj.snackbar = true;
                    currentObj.text = mensaje_error;
                }

                console.log(error.message);
                console.log(error.response.data);
                console.log(error.response.status);
                console.log(error.response.headers);
            })            

        },
        abrirMindmap(formulario){
            var currentObj = this;
            var archv_formulario = document.getElementById('xmlfile_load');
            var files = archv_formulario.files;
            if(files.length > 0){
                var file_data = files[0];
                jsMind.util.file.read(file_data,function(jsmind_data, jsmind_name){
                    var mind = jsMind.util.json.string2json(jsmind_data);
                    if(!!mind){
                        currentObj.data.show(mind);
                    }else{
                        prompt_info('No se puede abrir el archivo como Mindmap');
                    }
                });
            }else{
                prompt_info('Selecciona un archivo primero.')
            }
            /*formulario.preventDefault();
            let currentObj = this
            var mind_data = currentObj.data.get_data('node_array');
            var mind_name = mind_data.meta.name;
            var mind_str = jsMind.util.json.json2string(mind_data);
            jsMind.util.file.save(mind_str,'text/jsmind',mind_name+'.json');
            --
            var datos_exportar = currentObj.data.get_data() //get data obtiene el mind del modelo jsmind 
            var mind_string = jsMind.util.json.json2string(datos_exportar);
            var nombre_arquetipo = currentObj.nombre_arch_expor
            var exportObj = mind_string
            var exportName= nombre_arquetipo+"data"
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportObj));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href",     dataStr);
            downloadAnchorNode.setAttribute("download", exportName + ".json");
            document.body.appendChild(downloadAnchorNode); // required for firefox
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
            */
        },
        crearDiv(id){
            let currentObj = this;
            var c = document.getElementById("main").getElementsByTagName('div').length;
            var x =  document.getElementById("main").getElementsByTagName('div');
            var div = document.createElement("div");
            div.style.width = "750px";
            div.style.height = "450px";
            div.style.border = "solid 1px #ccc";
            div.style.background = "#f4f4f4";
            var id = c+1;
            div.id = "jsmind_container"+id;
            var id_btn = div.id;      
            var boton = currentObj.crearBtn(id_btn,"Guardar",0);
            var boton_exportar = currentObj.crearBtn(id_btn,"Exportar",1);
            var q = document.createElement("br");
            document.getElementById("main").appendChild(q);
            document.getElementById("main").appendChild(div);
            document.getElementById(div.id).appendChild(boton);
            document.getElementById(div.id).appendChild(document.createElement("br"));
            document.getElementById(div.id).appendChild(document.createElement("br"));
            document.getElementById(div.id).appendChild(boton_exportar);
            document.getElementById("main").appendChild(document.createElement("br"));
            document.getElementById("main").appendChild(document.createElement("br"));
            document.getElementById("main").appendChild(document.createElement("br"));
            return div.id;
        },
        crearBtn(id_btn,texto,tipo){
            var currentObj = this 
            if(tipo == 0){
                var b = document.createElement("button");
                b.style.color = "white";
                b.id = id_btn+"_button";
                b.style.backgroundColor = "green";
                b.innerHTML = texto;
                b.onclick = function () {
                    //ACA CODIGO PARA PASAR A PROCESARLO
                };
                return b;
            }else{
                var b = document.createElement("button");
                b.style.color = "white";
                b.id = id_btn+"_export";
                b.style.backgroundColor = "green";
                b.innerHTML = texto;
                b.onclick = function () {
                    var mind_data = currentObj.data.get_data('node_array');
                    var mind_name = mind_data.meta.name;
                    var mind_str = jsMind.util.json.json2string(mind_data);
                    jsMind.util.file.save(mind_str,'text/jsmind',mind_name+'.json'); 
                };
                return b;
            }

        },
    }
}
</script>
<style lang="scss">
    @import '../../../node_modules/vuetify/src/styles/main';
</style>
