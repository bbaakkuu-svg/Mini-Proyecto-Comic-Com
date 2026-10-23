# Comic-Con DAW 2026

Aplicación web de inscripción en un único archivo PHP (`comicon.php`). Muestra el formulario, valida los datos en el servidor, calcula el precio y presenta el resguardo de la inscripción.

## Requisitos y ejecución

- PHP 8.0 o superior.
- Desde la carpeta del proyecto, ejecuta `php -S localhost:8000` y abre <http://localhost/Mini-Proyecto-Comic-Com/comicon.php>.

## Entradas y precios

| Tipo | Precio por entrada |
| --- | ---: |
| General | 30 € |
| VIP | 60 € |
| Infantil | 10 € |

El formulario solicita nombre completo, edad y tipo de entrada, y permite seleccionar cualquiera de estos talleres. Incluye un botón para limpiar los datos y el resguardo mostrado. La entrada VIP está restringida a mayores de edad y la entrada infantil, a menores de 18 años:

| Taller | Precio |
| --- | ---: |
| Torneo de Videojuegos | 10 € |
| Taller de Cosplay | 15 € |
| Zona de Rol en Vivo | 5 € |

El precio se calcula en el servidor al procesar el formulario. El formulario desactiva la entrada infantil cuando se indica una edad de 18 años o más; el servidor también rechaza esa combinación.

La validación del servidor comprueba los campos, el tipo de entrada, las restricciones de edad y los talleres seleccionados. Los datos que se vuelven a mostrar en el HTML se escapan para evitar que contenido enviado por el usuario se interprete como marcado. Cada envío del formulario registra en el log del servidor la fecha y si el procesamiento fue exitoso o fallido.