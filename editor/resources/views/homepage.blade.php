<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta charset="utf-8">
        <
        <link href="https://cdn.jsdelivr.net/npm/@mdi/font@3.x/css/materialdesignicons.min.css" rel="stylesheet">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Editor De Arquetipos</title>
        <link href="{{asset('css/app.css')}}" rel="stylesheet"> <!--Añadimos el css generado con webpack- {{asset('css/app.css')}}-->
        <link href="{{asset('bootstrap.js')}}" rel="stylesheet">
    </head>
    <body>
            <div id="app">
              <example-component></example-component>
            <div>
            <div id="app"> <!--La equita id debe ser app-->
                <homepage-component></homepage-component><!--Añadimos nuestro componente vuejs-->
            </div>
        <script src="{{asset('js/app.js')}}"></script> <!--Añadimos el js generado con webpack, donde se encuentra nuestro componente vuejs-->
        <script>src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"        </script>
        <script>src="https://cdn.jsdelivr.net/npm/babel-polyfill/dist/polyfill.min.js"</script>
        <script> src="https://cdn.jsdelivr.net/npm/vuetify@2.0.18/dist/vuetify.min.js"</script>
    </body>
</html>
