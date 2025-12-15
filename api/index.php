<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Lector RSS</title>
    </head>
    <body>
        <form action="index.php">
            <fieldset> 
                <legend>FILTRO</legend>
                <label>PERIODICO : </label>
                <select type="selector" name="periodicos">
                    <option name="elpais">El Pais</option>
                    <option name="elmundo">El Mundo</option>      
                </select> 
                <label>CATEGORIA : </label>
                <select type="selector" name="categoria" value="">
                    <option name=""></option>
                    <option name="Política">Política</option>
                    <option name="Deportes">Deportes</option>
                    <option name="Ciencia">Ciencia</option>
                    <option name="España">España</option>
                    <option name="Economía">Economía</option>
                    <option name="Música">Música</option>
                    <option name="Cine">Cine</option>
                    <option name="Europa">Europa</option>
                    <option name="Justicia">Justicia</option>                
                </select>
                <label>FECHA : </label>
                <input type="date" name="fecha" value=""></input>
                <label style="margin-left: 5vw;">AMPLIAR FILTRO (la descripción contenga la palabra) : </label>
                <input type="text" name="buscar" value=""></input>
                <input type="submit" name="filtrar">
            </fieldset>
        </form>
        
        <?php
        
        // NOTA IMPORTANTE: Estos archivos también deben ser actualizados a PDO
        // si contienen inserciones a base de datos.
        require_once "RSSElPais.php";
        require_once "RSSElMundo.php";
        
        // Función adaptada para usar la conexión de `pg_connect`
        function filtros($sql, $link){
            $result = pg_query($link, $sql);
            if (!$result) {
                echo "<tr><td colspan='6'>Error en la consulta: " . pg_last_error($link) . "</td></tr>";
                return;
            }

            while ($arrayFiltro = pg_fetch_assoc($result)) {
                echo"<tr>";
                    echo "<th style='border: 1px #E4CCE8 solid;'>" . ($arrayFiltro['titulo'] ?? '') . "</th>";
                    echo "<th style='border: 1px #E4CCE8 solid;'>" . ($arrayFiltro['contenido'] ?? '') . "</th>";
                    echo "<th style='border: 1px #E4CCE8 solid;'>" . ($arrayFiltro['descripcion'] ?? '') . "</th>";
                    echo "<th style='border: 1px #E4CCE8 solid;'>" . ($arrayFiltro['categoria'] ?? '') . "</th>";
                    echo "<th style='border: 1px #E4CCE8 solid;'>" . ($arrayFiltro['link'] ?? '') . "</th>";

                    // Manejo de fecha seguro (campo en la BD: fpubli)
                    if (!empty($arrayFiltro['fpubli'])) {
                        $fecha = date_create($arrayFiltro['fpubli']);
                        $fechaConversion = $fecha ? date_format($fecha,'d-M-Y') : 'Sin fecha';
                    } else {
                        $fechaConversion = "Sin fecha";
                    }

                    echo "<th style='border: 1px #E4CCE8 solid;'>" . $fechaConversion . "</th>";
                echo"</tr>";
            }
        }
        
        require_once "conexionBBDD.php";
        
        // Verificamos si $link existe (creado en conexionBBDD.php)
        if(!isset($link)){
             printf("Conexión fallida: No se pudo conectar a la BBDD.");
        } else {
       
            echo"<table style='border: 5px #E4CCE8 solid;'>";
            echo"<tr><th><p style='color: #66E9D9;'>TITULO</p ></th><th><p  style='color: #66E9D9;'>CONTENIDO</p ></th><th><p  style='color: #66E9D9;'>DESCRIPCIÓN</p ></th><th><p  style='color: #66E9D9;'>CATEGORÍA</p ></th><th><p  style='color: #66E9D9;'>ENLACE</p ></th><th><p  style='color: #66E9D9;'>FECHA DE PUBLICACIÓN</p ></th></tr>"."<br>";

            if(isset($_REQUEST['filtrar'])){

             $periodicos= str_replace(' ','',$_REQUEST['periodicos']);
             $periodicosMin=strtolower($periodicos);
            
                $cat = $_REQUEST['categoria'];
                $f = $_REQUEST['fecha'];
                $palabra = $_REQUEST["buscar"];
                 
                // Usamos ILIKE en lugar de LIKE para que Postgres busque ignorando mayúsculas/minúsculas
                
                //FILTRO PERIODICO
                if($cat=="" && $f=="" && $palabra==""){
                 $sql="SELECT * FROM ".$periodicosMin." ORDER BY fPubli desc";
                 filtros($sql,$link);
                }

                //FILTRO CATEGORIA
                   if($cat!="" && $f=="" && $palabra==""){ 
                    $sql="SELECT * FROM ".$periodicosMin." WHERE categoria ILIKE '%$cat%'";
                    filtros($sql,$link);
                    }

                    //FILTRO FECHA
                       if($cat=="" && $f!="" && $palabra==""){
                           $sql="SELECT * FROM ".$periodicosMin." WHERE fPubli='$f'";
                           filtros($sql,$link);
                        }

                        //FILTRO CATEGORIA Y FECHA
                            if($cat!="" && $f!="" && $palabra==""){ 
                              $sql="SELECT * FROM ".$periodicosMin." WHERE categoria ILIKE '%$cat%' and fPubli='$f'";
                              filtros($sql,$link);
                            }

                            //FILTRO TODO
                             if($cat!="" && $f!="" && $palabra!=""){ 
                              $sql="SELECT * FROM ".$periodicosMin." WHERE descripcion ILIKE '%$palabra%' and categoria ILIKE '%$cat%' and fPubli='$f'";
                              filtros($sql,$link);
                            }  

                            //FILTRO CATEGORIA PALABRA
                            if($cat!="" && $f=="" && $palabra!=""){ 
                              $sql="SELECT * FROM ".$periodicosMin." WHERE descripcion ILIKE '%$palabra%' and categoria ILIKE '%$cat%'";
                              filtros($sql,$link);
                            } 

                            //FILTRO FECHA Y PALABRA 
                             if($cat=="" && $f!="" && $palabra!=""){ 
                              $sql="SELECT * FROM ".$periodicosMin." WHERE descripcion ILIKE '%$palabra%' and fPubli='$f'";
                              filtros($sql,$link);
                            }  

                            //FILTRO PALABRA
                            if($palabra!="" && $cat=="" && $f=="" ){ 
                              $sql="SELECT * FROM ".$periodicosMin." WHERE descripcion ILIKE '%$palabra%' ";
                              filtros($sql,$link);
                            }  
                
            }else{
                            $sql="SELECT * FROM elpais ORDER BY fPubli desc";
                            filtros($sql,$link);
            }
                  
        }
        
        echo"</table>";   
        ?>
        
    </body>
</html>