<?php
//Pongo que se muestren todos los errores para depurar mejor
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ================= BACKEND ===================
//Solo se ejecuta esta parte si hay variables POST y el servicio está registrado

define("SEGUNDOS_PETICION",10);//Segundos a esperar por cada peticion de datos-paginas

//Recolectar variables POST si los hay
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
    //Receive the RAW post data.
    //Recibimos un string JSON a decodificar
    $inputVar = json_decode(trim(file_get_contents("php://input")),true);
    
    if(isset($inputVar["info"])){
        $servicio=$inputVar["info"];

        //Si hay mas,sustituimos un swtich y si crece mas, creamos una clase caller
        if($servicio=="venta_online"){

            //Si tenemos pagina, seguimos pidiendo por la que ibamos en rango de 10 sec
            if(isset($inputVar["pagina"]) AND isset($inputVar["ultimaPagina"])){
                $elementos=obtenerDatos((int)$inputVar["pagina"],(int)$inputVar["ultimaPagina"]);
            }else{
                //Si no, es la primera vez y empezamos desde el final
                $elementos=obtenerDatos();
            }
            die(json_encode(array("status"=>"OK","datos"=>$elementos)));
        }else if($servicio=="datos_tabla"){
            //Esta parte pinta el html de la tabla
            $cadenaTabla=pintarElementos($inputVar["registros"]);
            die(json_encode(array("status"=>"OK","datos_html"=>$cadenaTabla)));
        }else{
            die(json_encode(array("status"=>"KO","error"=>"Servicio no encontrado")));
        }
    }
}
//============================ FIN BACKEND ========================

// ======================= FRONTEND HTML-JS =====================
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
        <meta charset="utf-8">
 
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    
    </head>
    <body>

        <div class="container">
        
            <div class="row">

                <div class="col align-self-center" style="text-align: center;">
                    <img src="https://media1.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif" id="myLoadingGif" style="">
                </div>

                <table class="table">
                    <thead> 
                        <tr>
                            <th>Pais</th>
                            <th>Codigo</th>
                            <th>Banco</th>
                <!--    <th>Moneda</th> -->
                            <th>Monto minimo</th>     
                            <th>Monto maximo</th>
                            <th>Precio</th>
                            <th>Precio dolar</th>
                <!--    <th>Mensaje</th>-->
                        </tr>
                    </thead>
                    <tbody id="datos_listado">
                    </tbody>
                </table>

            </div>

            <script type="text/javascript">
            
                var url = 'banesco_1.php';
                var data = {info: 'venta_online'};
                var registros=[];
                $( document ).ready(function() {
                    buscarDatos();
                });
                
                function buscarDatos(pagina,ultPagina){

                    if(typeof pagina!="undefined" && typeof ultPagina!="undefined"){
                        data["pagina"]=pagina;
                        data["ultimaPagina"]=ultPagina;
                    }

                    //Metodo para peticiones HTTP al servidor
                    fetch(url, {
                        method: 'POST', // or 'PUT'
                        body: JSON.stringify(data), // data can be `string` or {object}!
                        headers:{
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(function(res){
                        return res.json();
                    })
                    .catch(function(error){
                        console.error('Error:', error);
                    })
                    .then(function(response){
                        console.log(response);

                        for(let x in response.datos.registros){
                            registros.push(response.datos.registros[x]);
                        }
                        console.log(registros);
                        if(response.datos.pagina!=response.datos.ultimaPagina){
                            buscarDatos(response.datos.pagina,response.datos.ultimaPagina);
                        }else{
                            //Ordenamos de forma descendente por precio
                            registros.sort(function(a,b){return parseFloat(b.temp_price_usd)-parseFloat(a.temp_price_usd)});
                            pintarDatos(registros);
                        }
                        
                    });
                }

                function pintarDatos(elementos){

                    let data_pintar = {info: 'datos_tabla','registros':elementos};
                    fetch(url, {
                        method: 'POST', // or 'PUT'
                        body: JSON.stringify(data_pintar), // data can be `string` or {object}!
                        headers:{
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(function(res){
                        return res.json();
                    })
                    .catch(function(error){
                        console.error('Error:', error);
                    })
                    .then(function(response){
                        console.log(response);
                        $("#datos_listado").html(response['datos_html']);
                        document.getElementById('myLoadingGif').style.display = 'none';
                    });
                }
            </script> 
        </div>
    </body>
</html>

<?php
// ==================== FIN FRONTEND HTML-JS ======================

//================= FUNCIONES BACKEND =====================

//LLAMADA A LA API POR PAGINACIÓN 
function callAPI($page){
 
    $url  = "https://localbitcoins.com/sell-bitcoins-online/transfers-with-specific-bank/.json";
 
    $curl = curl_init();
    if ($page > 1) {
 
        $url = sprintf("%s?%s", $url, http_build_query(array(
 
            'page' => $page
 
        )));
 
    }

    curl_setopt($curl, CURLOPT_URL, $url);
 
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));

    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // EXECUTE:
 
    $result = curl_exec($curl);
    
    if (!$result) {
        die("Connection Failure");
    }
 
    curl_close($curl);
 
    return $result;
 
}

//Metodo que buscar por pagina, en tramos de segundos para evitar el timeout
function obtenerDatos($pagina=0,$ultimaPagina=1000){
    //La primera vez la pagina es cero para la primera carga
    $listData = array();
    
    //SE PAGINA YA QUE LA API SOLO RETORNA UN LIMITE
    $tiemposInicio=microtime(true);
    //Como he visto que la pagina tiene un control de maximo, pido primero la pagina 1000, para que me de la ultima
    // y sobre la ultima pagina calculo el numero de iteraciones

    //Si es la primera vez buscamos la ultima pagina
    if($pagina===0){
        $ultimaPagina=1000;
        $data = callAPI($ultimaPagina);
        $jsonData = json_decode($data);

        
        if(isset($jsonData->pagination->prev)){
            $urlUltimaPagina=$jsonData->pagination->prev;
            $ultimaPagina=(int)buscarPaginaUrl($urlUltimaPagina);
        }else{
            //Error retornamos vacio
            return "";
        }
        //echo "\nUltima pagina:".$ultimaPagina;
        //Agreamos el los primeros valores, que son de la ultima pagina
        foreach ($jsonData->data->ad_list as $key => $value) {
            $registroValido=filtrarDatos($value);
            //Si tiene datos agregamos
            if($registroValido){
                $listData[]=$registroValido;
            }
        }
    }

    //No procesamos la ultima pagina($ultimaPagina-1) porque la hemos procesado al principio para calcular las iteraciones
    while($pagina<=($ultimaPagina-1)){
        
        $pagina++;
        $tiempoActual=microtime(true);
        if(($tiempoActual-$tiemposInicio)>SEGUNDOS_PETICION){
            break;
        }

        //Llamada a la api publica de datos
        $data = callAPI($pagina);
        $jsonData = json_decode($data);
        
        //Agregamos los registros de la pagina pagina
        foreach ($jsonData->data->ad_list as $key => $value) {
            
            $registroValido=filtrarDatos($value);
            
            //Si tiene datos validos agregamos
            if($registroValido){
                $listData[]=$registroValido;
            }
        }
        usleep(10000);//0.01 segundo de espera para no saturar al server al que pedimos, por si tiene control de peticiones masivas
    }

    $resultado=array(
        "registros"=>$listData,
        "pagina"=>$pagina,
        "ultimaPagina"=>$ultimaPagina
    );

    return $resultado;
}

//Ver si el registro cumple los valores deseados
function filtrarDatos($value){
    
    $BANK      = "banesco";
 
    $LIMIT_MIN = 0;
    
    $LIMIT_MAX = 10;
    
    $MONEDA    = 'VES';

    //VERIFICO EL CAMPO BANK_NAME
    $tempData= array();
    if (property_exists($value->data, "bank_name")) {
        
        //FILTRO POR BANCO - MONEDA Y MONTO
        if (preg_match("/" . $BANK . "/i", $value->data->bank_name) && ($value->data->currency == $MONEDA && ($value->data->min_amount >= $LIMIT_MIN))) {

            $tempData['countrycode']     = $value->data->countrycode;

            //$tempData['city']            = $value->data->city;

            //$tempData['location_string'] = $value->data->location_string;

            $tempData['bank_name']       = $value->data->bank_name;

            $tempData['currency']        = $value->data->currency;

            $tempData['min_amount']      = $value->data->min_amount;

            $tempData['max_amount']      = $value->data->max_amount;

            $tempData['temp_price']  = $value->data->temp_price;

            $tempData['temp_price_usd']  = $value->data->temp_price_usd;

            $tempData['ad_id']           = $value->data->ad_id;
            //SE GUARDA LA INFORMACIÓN RECIBIDA POR LA API
        }

    }
    
    return $tempData;
}

// Para buscar la pagina en la URL
function buscarPaginaUrl($url){

    $ultimaPagina=1000;
    //Procesamos la url,separando la url de las variables
    $infoUrl=explode("?",$url);
    //Parte de las variables GET, por si hay mas de una variable
    $variablesGet=$infoUrl[1];
    $infoVariablesGet=explode("&",$variablesGet);
    //buscamos valor "page"
    foreach($infoVariablesGet as $nVariable){
        list($campo,$valor)=explode("=",$nVariable);
        if($campo=="page"){
            //Sustituimos la variable
            $ultimaPagina=$valor;
            //Cortamos bucle
            break;
        }
    }

    return $ultimaPagina;
}

//Pintar la parte de la tabla con los datos
function pintarElementos($elementos){

    $cadenaTabla="";
    //ARMO EL CUERPO DE LA TABLA
    foreach ($elementos as $key => $value) { 
    
        $cadenaTabla.="<tr>";
    
        $cadenaTabla.= "<td>" . $value['countrycode'] . "</td>";
    
        $cadenaTabla.= "<td>"."<a href=https://localbitcoins.com/ad/".$value['ad_id'].">" . $value['ad_id'] . "</a>"."</td>";
        
    //echo "<td>" . $value->city . "</td>";
    
    //echo "<td>-" . $value->location_string . "</td>";
    
        $cadenaTabla.= "<td>" . $value['bank_name'] . "</td>";
    
    // echo "<td>" . $value->currency . "</td>";
    
        $cadenaTabla.= "<td>" . number_format($value['min_amount'], 2, ',', '.') . "</td>";
        $cadenaTabla.= "<td>" . number_format($value['max_amount'], 2, ',', '.') . "</td>";
        $cadenaTabla.= '<td style="font-weight:bold;color:green"> ' .  number_format($value['temp_price'], 2, ',', '.') . " VES</td>";
        $cadenaTabla.= "<td>" . ' $' . number_format($value['temp_price_usd'], 2, ',', '.') . "</td>";
        //echo "<td>-".$value->data ->msg."</td>";
        $cadenaTabla.= "</tr>";
    }

    return $cadenaTabla;
}
?>