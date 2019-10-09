<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/@mdi/font@4.x/css/materialdesignicons.min.css" rel="stylesheet">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Editor De Arquetipos</title>
        <style type="text/css">
            #jsmind_container{
                width:800px;
                height:500px;
                border:solid 1px #ccc;
                /*background:#f4f4f4;*/
                background:#f4f4f4;
            }
        </style>
        <!--<link type="text/css" rel="stylesheet" href="sass/jsmind.css">
        <script src="js/jsmind.js"></script>-->
        <link href="{{asset('css/app.css')}}" rel="stylesheet">
        <!--<link href="../../node_modules/jsmind/style/jsmind.css" rel="stylesheet">-->
        <!--<link href="{{asset('bootstrap.js')}}" rel="stylesheet">-->
    </head>
    <body>
            <div id="app">
              <example-component></example-component>
              <crear-component></crear-component>
            </div>
            <script src="{{asset('js/app.js')}}"></script> <!--Añadimos el js generado con webpack, donde se encuentra nuestro componente vuejs-->
    </body>
</html>