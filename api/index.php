<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lector RSS Noticias</title>
        <style>
            /* ESTILOS VISUALES (CSS) */
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f0f2f5;
                margin: 0;
                padding: 20px;
                color: #333;
            }

            /* Contenedor principal para centrar */
            .main-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            /* Estilo del Formulario */
            form {
                background-color: white;
                padding: 25px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                margin-bottom: 30px;
            }

            fieldset {
                border: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                align-items: flex-end;
            }

            legend {
                font-size: 1.5rem;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 20px;
                width: 100%;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
            }

            .form-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            label {
                font-weight: 600;
                font-size: 0.9rem;
                color: #555;
            }

            select, input[type="date"], input[type="text"] {
                padding: 10px 15px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 1rem;
                background-color: #fff;
            }

            input[type="submit"] {
                background-color: #3498db;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 6px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.3s ease;
            }

            input[type="submit"]:hover {
                background-color: #2980b9;
            }

            /* Estilo de la Grid de Noticias (Sustituye a la tabla) */
            .news-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 25px;
            }

            /* Estilo de cada Tarjeta de Noticia */
            .card {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
                transition: transform 0.2s;
                display: flex;
                flex-direction: column;
                height: 100%;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            }

            .card-image {
                height: 180px;
                background-color: #ddd;
                overflow: hidden;
            }
            
            .card-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .card-content {
                padding: 20px;
                display: flex;
                flex-direction: column;
                flex-grow: 1;
            }

            .card-meta {
                display: flex;
                justify-content: space-between;
                font-size: 0.8rem;
                color: #888;
                margin-bottom: 10px;
            }

            .tag {
                background-color: #e1f0fa;
                color: #3498db;
                padding: 3px 8px;
                border-radius: 4px;
                font-weight: bold;
            }

            .card h3 {
                margin: 0 0 10px 0;
                font-size: 1.2rem;
                line-height: 1.4;
                color: #2c3e50;
            }

            .card p {
                font-size: 0.95rem;
                color: #666;
                line-height: 1.6;
                margin-bottom: 20px;
                /* Cortar texto si es muy largo */
                display: -webkit-box;
                -webkit-line-clamp: 4;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .btn-read {
                margin-top: auto;
                display: inline-block;
                text-decoration: none;
                color: #3498db;
                font-weight: bold;
            }
            
            .btn-read:hover {
                text-decoration: underline;
            }
            
            .error-msg {
                background-color: #fee;
                color: #c00;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                margin-top: 20px;
            }

        </style>
    </head>
    <body>
        <div class="main-container">
            <form action="index.php">
                <fieldset> 
                    <legend>Filtro de Noticias</legend>
                    
                    <div class="form-group">
                        <label>PERIÓDICO</label>
                        <select name="periodicos">
                            <option value="El Pais">El Pais</option>
                            <option value="El Mundo">El Mundo</option>      
                        </select> 
                    </div>

                    <div class="form-group">
                        <label>CATEGORÍA</label>
                        <select name="categoria">
                            <option value=""></option>
                            <option value="Política">Política</option>
                            <option value="Deportes">Deportes</option>
                            <option value="Ciencia">Ciencia</option>
                            <option value="España">España</option>
                            <option value="Economía">Economía</option>
                            <option value="Música">Música</option>
                            <option value="Cine">Cine</option>
                            <option value="Europa">Europa</option>
                            <option value="Justicia">Justicia</option>                
                        </select>
                    </div>

                    <div class="form-group">
                        <label>FECHA</label>
                        <input type="date" name="fecha" value="">
                    </div>

                    <div class="form-group" style="flex-grow: 1;">
                        <label>BUSCAR (Palabra clave)</label>
                        <input type="text" name="buscar" placeholder="Ej: elecciones..." value="">
                    </div>

                    <input type="submit" name="filtrar" value="FILTRAR">
                </fieldset>
            </form>
            
            <div class="news-container">
                <?php
                
                require_once "RSSElPais.php";
                require_once "RSSElMundo.php";

                // Función MODIFICADA VISUALMENTE (la lógica interna de conexión es la misma)
                // Ahora imprime tarjetas DIV en lugar de TR de tabla
                function filtros($sql, $link)
                {
                    $result = pg_query($link, $sql);

                    if (!$result) {
                        echo "<div class='error-msg'>Error en la consulta: " . pg_last_error($link) . "</div>";
                        return;
                    }

                    while ($arrayFiltro = pg_fetch_assoc($result)) {
                        
                        // Preparación de datos visuales
                        $titulo = $arrayFiltro['titulo'];
                        $desc = strip_tags($arrayFiltro['descripcion']); // Limpiamos HTML de la descripción
                        $cat = $arrayFiltro['categoria'];
                        $enlace = $arrayFiltro['link'];
                        
                        // Fecha
                        if (isset($arrayFiltro['fpubli']) && $arrayFiltro['fpubli']) {
                            $fechaObj = date_create($arrayFiltro['fpubli']);
                            $fechaTexto = date_format($fechaObj, 'd-M-Y');
                        } else {
                            $fechaTexto = "Sin fecha";
                        }

                        // Imagen (Intentamos usar la columna imagen si existe, si no, placeholder)
                        $imgSrc = isset($arrayFiltro['imagen']) && !empty($arrayFiltro['imagen']) 
                                  ? $arrayFiltro['imagen'] 
                                  : 'https://via.placeholder.com/400x200?text=Noticia';

                        // AQUÍ ESTÁ EL CAMBIO DE ESTILO (HTML)
                        echo "
                        <article class='card'>
                            <div class='card-image'>
                                <img src='$imgSrc' alt='Imagen noticia' loading='lazy'>
                            </div>
                            <div class='card-content'>
                                <div class='card-meta'>
                                    <span class='date'>$fechaTexto</span>
                                    <span class='tag'>$cat</span>
                                </div>
                                
                                <h3><a href='$enlace' target='_blank' style='text-decoration:none; color:inherit;'>$titulo</a></h3>
                                <p>$desc</p>
                                
                                <a href='$enlace' target='_blank' class='btn-read'>Leer noticia completa &rarr;</a>
                            </div>
                        </article>
                        ";
                    }
                }
                
                require_once "conexionBBDD.php";
                
                if(!isset($link)){
                     printf("<div class='error-msg'>Conexión fallida: No se pudo conectar a la BBDD.</div>");
                } else {
               
                    // AQUÍ COMIENZA TU LÓGICA ORIGINAL INTACTA
                    if(isset($_REQUEST['filtrar'])){

                     $periodicos= str_replace(' ','',$_REQUEST['periodicos']);
                     $periodicosMin=strtolower($periodicos);
                    
                        $cat = $_REQUEST['categoria'];
                        $f = $_REQUEST['fecha'];
                        $palabra = $_REQUEST["buscar"];
                         
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
                    // FIN DE TU LÓGICA ORIGINAL
                          
                }
                ?>
            </div> </div> </body>
</html>