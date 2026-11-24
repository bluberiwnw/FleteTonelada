<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/db.php';
requiereRol('cliente');
$usuarioId = (int) $_SESSION['usuario']['id'];
$cargaId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$cargaId) { http_response_code(400); exit('Identificador no válido.'); }
$q = $pdo->prepare("SELECT c.id,c.estado,v.transportista_id,u.nombre AS contraparte FROM cargas c INNER JOIN viajes v ON v.carga_id=c.id INNER JOIN usuarios u ON u.id=v.transportista_id WHERE c.id=:carga AND c.cliente_id=:cliente LIMIT 1");
$q->execute([':carga' => $cargaId, ':cliente' => $usuarioId]);
$carga = $q->fetch();
if (!$carga || $carga['estado'] !== 'entregada') { http_response_code(404); exit('La carga no está disponible para calificar.'); }
$errores = [];
$yaCalifico = false;
$check = $pdo->prepare('SELECT id FROM calificaciones WHERE carga_id=:carga AND autor_id=:autor');
$check->execute([':carga' => $cargaId, ':autor' => $usuarioId]);
$yaCalifico = (bool) $check->fetch();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$yaCalifico) {
    validarTokenCsrf();
    $estrellas = filter_var($_POST['estrellas'] ?? null, FILTER_VALIDATE_INT);
    $comentario = trim(is_string($_POST['comentario'] ?? null) ? $_POST['comentario'] : '');
    if (!$estrellas || $estrellas < 1 || $estrellas > 5) $errores[] = 'Selecciona una calificación de 1 a 5 estrellas.';
    if (mb_strlen($comentario) > 2000) $errores[] = 'El comentario no puede superar 2000 caracteres.';
    if (!$errores) {
        try {
            $insertar = $pdo->prepare('INSERT INTO calificaciones (carga_id, autor_id, evaluado_id, estrellas, comentario) VALUES (:carga,:autor,:evaluado,:estrellas,:comentario)');
            $insertar->execute([':carga' => $cargaId, ':autor' => $usuarioId, ':evaluado' => $carga['transportista_id'], ':estrellas' => $estrellas, ':comentario' => $comentario !== '' ? $comentario : null]);
            establecerMensaje('success', 'Tu calificación fue registrada.');
            header('Location: ' . RUTA_BASE . '/cliente/carga.php?id=' . $cargaId); exit;
        } catch (PDOException $e) {
            error_log('Calificación: ' . $e->getMessage());
            $errores[] = 'No fue posible guardar la calificación. Es posible que ya hayas calificado esta carga.';
        }
    }
}
$tituloPagina = 'Calificar servicio | FleteTonelada';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="dashboard-page container" id="contenido"><section class="surface-card form-card narrow-card"><span class="eyebrow eyebrow-dark">OPINIÓN DEL CLIENTE</span><h1>Calificar al transportista</h1><p class="muted">Servicio #<?= (int) $cargaId ?> · <?= e($carga['contraparte']) ?></p><?php if ($yaCalifico): ?><div class="notice notice-success">Ya registraste tu calificación para este servicio.</div><?php else: ?><?php if ($errores): ?><div class="alert alert-error"><ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="POST" class="form-stack"><input type="hidden" name="token_csrf" value="<?= e(obtenerTokenCsrf()) ?>"><div class="form-field"><label for="estrellas">Calificación (1 a 5 estrellas)</label><select id="estrellas" name="estrellas" required><option value="">Selecciona</option><?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> estrella(s)</option><?php endfor; ?></select></div><div class="form-field"><label for="comentario">Comentario (opcional)</label><textarea id="comentario" name="comentario" rows="4" maxlength="2000"></textarea></div><button class="button button-primary" type="submit">Enviar calificación</button></form><?php endif; ?><a class="text-link top-gap" href="<?= RUTA_BASE ?>/cliente/carga.php?id=<?= (int) $cargaId ?>">Volver al detalle</a></section></main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
