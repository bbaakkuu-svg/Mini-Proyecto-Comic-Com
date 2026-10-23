<?php
declare(strict_types=1);

const TIPOS_ENTRADA = [
    'general' => ['nombre' => 'General', 'precio' => 30],
    'vip' => ['nombre' => 'VIP', 'precio' => 60],
    'infantil' => ['nombre' => 'Infantil', 'precio' => 10],
];

const MAYORIA_DE_EDAD = 18;

const TALLERES = [
    'videojuegos' => ['nombre' => 'Torneo de Videojuegos', 'precio' => 10],
    'cosplay' => ['nombre' => 'Taller de Cosplay', 'precio' => 15],
    'rol' => ['nombre' => 'Zona de Rol en Vivo', 'precio' => 5],
];

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function obtenerTexto(array $datos, string $campo): string
{
    return isset($datos[$campo]) && is_string($datos[$campo])
        ? trim($datos[$campo])
        : '';
}

function calcularPrecioBase(string $tipo): int
{
    if (!array_key_exists($tipo, TIPOS_ENTRADA)) {
        throw new InvalidArgumentException('Selecciona un tipo de entrada válido.');
    }

    return TIPOS_ENTRADA[$tipo]['precio'];
}

function procesarInscripcion(string $tipo, array $talleres): array
{
    $precioBase = calcularPrecioBase($tipo);
    $precioTalleres = 0;

    foreach ($talleres as $taller) {
        if (!is_string($taller) || !array_key_exists($taller, TALLERES)) {
            throw new InvalidArgumentException('La selección de talleres no es válida.');
        }

        $precioTalleres += TALLERES[$taller]['precio'];
    }

    return [
        'precioBase' => $precioBase,
        'precioTalleres' => $precioTalleres,
        'total' => $precioBase + $precioTalleres,
    ];
}

$error = '';
$inscripcion = null;
$precios = null;
$nombre = '';
$edadTexto = '';
$tipoEntrada = '';
$talleresSeleccionados = [];
$procesamientoExitoso = false;

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && obtenerTexto($_POST, 'accion') !== 'limpiar'
) {
    try {
        $nombre = obtenerTexto($_POST, 'nombre');
        $edadTexto = obtenerTexto($_POST, 'edad');
        $tipoEntrada = obtenerTexto($_POST, 'tipo_entrada');
        $talleresSeleccionados = $_POST['talleres'] ?? [];

        if ($nombre === '') {
            throw new InvalidArgumentException('Introduce tu nombre completo.');
        }

        $edad = filter_var($edadTexto, FILTER_VALIDATE_INT);
        if ($edad === false || $edad <= 0) {
            throw new InvalidArgumentException('La edad debe ser un número entero positivo.');
        }

        if (!array_key_exists($tipoEntrada, TIPOS_ENTRADA)) {
            throw new InvalidArgumentException('Selecciona un tipo de entrada válido.');
        }

        if (!is_array($talleresSeleccionados)) {
            throw new InvalidArgumentException('La selección de talleres no es válida.');
        }

        if ($edad < MAYORIA_DE_EDAD && $tipoEntrada === 'vip') {
            throw new InvalidArgumentException('La entrada VIP está restringida a mayores de edad.');
        }

        if ($edad >= MAYORIA_DE_EDAD && $tipoEntrada === 'infantil') {
            throw new InvalidArgumentException('La entrada infantil está reservada a menores de 18 años.');
        }

        $talleresValidados = [];
        foreach ($talleresSeleccionados as $taller) {
            if (!is_string($taller) || !array_key_exists($taller, TALLERES)) {
                throw new InvalidArgumentException('La selección de talleres no es válida.');
            }

            if (!in_array($taller, $talleresValidados, true)) {
                $talleresValidados[] = $taller;
            }
        }

        $talleresSeleccionados = $talleresValidados;
        $precios = procesarInscripcion($tipoEntrada, $talleresSeleccionados);
        $inscripcion = [
            'nombre' => $nombre,
            'edad' => $edad,
            'tipoEntrada' => $tipoEntrada,
            'talleres' => $talleresSeleccionados,
        ];
        $procesamientoExitoso = true;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } finally {
        error_log(sprintf(
            '[%s] Procesamiento del formulario de Comic-Con: %s.',
            date('Y-m-d H:i:s'),
            $procesamientoExitoso ? 'exitoso' : 'fallido'
        ));
    }
}

if (!is_array($talleresSeleccionados)) {
    $talleresSeleccionados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscripción a la Comic-Con</title>
</head>
<body>
    <h1>Inscripción a la Comic-Con</h1>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <p>
            <label for="nombre">Nombre completo:</label>
            <input
                id="nombre"
                name="nombre"
                type="text"
                required
                value="<?= escapar($nombre) ?>"
            >
        </p>

        <p>
            <label for="edad">Edad:</label>
            <input
                id="edad"
                name="edad"
                type="number"
                min="1"
                step="1"
                required
                value="<?= escapar($edadTexto) ?>"
            >
        </p>

        <p>
            <label for="tipo_entrada">Tipo de entrada:</label>
            <select
                id="tipo_entrada"
                name="tipo_entrada"
                aria-describedby="tipo_entrada_ayuda"
                required
            >
                <option value="">Selecciona una entrada</option>
                <?php foreach (TIPOS_ENTRADA as $clave => $entrada): ?>
                    <option
                        value="<?= escapar($clave) ?>"
                        <?= $tipoEntrada === $clave ? 'selected' : '' ?>
                    ><?= escapar($entrada['nombre']) ?> (<?= $entrada['precio'] ?> euros)</option>
                <?php endforeach; ?>
            </select>
            <small id="tipo_entrada_ayuda" aria-live="polite">
                La entrada infantil está reservada a menores de 18 años.
            </small>
        </p>

        <fieldset>
            <legend>Talleres especiales:</legend>
            <?php foreach (TALLERES as $clave => $taller): ?>
                <p>
                    <label>
                        <input
                            type="checkbox"
                            name="talleres[]"
                            value="<?= escapar($clave) ?>"
                            <?= in_array($clave, $talleresSeleccionados, true) ? 'checked' : '' ?>
                        >
                        <?= escapar($taller['nombre']) ?> (<?= $taller['precio'] ?> euros)
                    </label>
                </p>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit">Enviar inscripción</button>
        <button type="submit" name="accion" value="limpiar" formnovalidate>Limpiar formulario</button>
    </form>

    <?php if ($inscripcion !== null && $precios !== null): ?>
        <section aria-labelledby="resguardo">
            <h2 id="resguardo">Resguardo de inscripción</h2>
            <p>Nombre del asistente: <?= escapar($inscripcion['nombre']) ?></p>
            <p>
                Tipo de entrada:
                <?= escapar(TIPOS_ENTRADA[$inscripcion['tipoEntrada']]['nombre']) ?>
                — <?= $precios['precioBase'] ?> euros
            </p>
            <h3>Talleres seleccionados</h3>
            <?php if ($inscripcion['talleres'] === []): ?>
                <p>No se han seleccionado talleres.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($inscripcion['talleres'] as $taller): ?>
                        <li>
                            <?= escapar(TALLERES[$taller]['nombre']) ?>
                            — <?= TALLERES[$taller]['precio'] ?> euros
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p>Precio total: <?= $precios['total'] ?> euros</p>
        </section>
    <?php endif; ?>

    <script>
        const edadInput = document.getElementById('edad');
        const tipoEntrada = document.getElementById('tipo_entrada');
        const opcionInfantil = tipoEntrada.querySelector('option[value="infantil"]');
        const ayudaTipoEntrada = document.getElementById('tipo_entrada_ayuda');

        function actualizarDisponibilidadInfantil() {
            const edad = Number(edadInput.value);
            const esMayorDeEdad = Number.isInteger(edad) && edad >= <?= MAYORIA_DE_EDAD ?>;
            const infantilSeleccionada = tipoEntrada.value === 'infantil';

            opcionInfantil.disabled = esMayorDeEdad;
            if (esMayorDeEdad && infantilSeleccionada) {
                tipoEntrada.value = '';
            }

            ayudaTipoEntrada.textContent = esMayorDeEdad
                ? 'La entrada infantil no está disponible para personas de 18 años o más.'
                : 'La entrada infantil está reservada a menores de 18 años.';
        }

        edadInput.addEventListener('input', actualizarDisponibilidadInfantil);
        actualizarDisponibilidadInfantil();
    </script>
</body>
</html>
