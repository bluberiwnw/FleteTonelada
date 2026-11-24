# FleteTonelada

Aplicación web académica que conecta a empresas que necesitan transportar carga pesada (3.5 a 30 toneladas) con transportistas independientes.

## Tecnologías

- HTML5 y CSS3 con enfoque Mobile First.
- JavaScript sin frameworks.
- PHP 8 con sesiones y PDO.
- MariaDB / MySQL con InnoDB.
- Git y GitHub para control de versiones.

No se utilizan Bootstrap, jQuery, Laravel ni Flask.

## Roles

- **Cliente:** cotiza, publica cargas, consulta el seguimiento, ve el PIN de entrega y califica al transportista.
- **Transportista:** registra unidades, consulta cargas compatibles, acepta servicios, actualiza estados y captura el PIN al entregar.
- **Administrador:** verifica transportistas, activa o desactiva cuentas y consulta cargas y métricas.

## Requisitos

- Debian con Apache 2.4 o compatible.
- PHP 8.1 o posterior (recomendado PHP 8.2+).
- Extensiones `pdo_mysql`, `mbstring` y `session`.
- MariaDB 10.6+ o MySQL 8+.
- Git.

## Instalación en Debian

1. Copia o clona la carpeta en `/var/www/html/fletetonelada`.
2. Crea la estructura de la base de datos y los datos iniciales:

   ```bash
   cd /var/www/html/fletetonelada
   sudo mariadb < sql/schema.sql
   sudo mariadb < sql/seed.sql
   ```

   `schema.sql` utiliza `CREATE TABLE IF NOT EXISTS`. `seed.sql` utiliza `INSERT IGNORE` para que los catálogos y las cuentas demo no se dupliquen al ejecutarlo otra vez.

3. Crea un usuario de base de datos exclusivo para la aplicación (si aún no existe):

   ```sql
   CREATE USER 'fletetonelada_app'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CONTRASENA';
   GRANT SELECT, INSERT, UPDATE, DELETE ON fletetonelada.* TO 'fletetonelada_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

   Si ya creaste el usuario, no vuelvas a ejecutar `CREATE USER`. Puedes cambiar su contraseña con `ALTER USER`.

4. Crea el archivo privado de conexión:

   ```bash
   cp config/db.example.php config/db.php
   nano config/db.php
   ```

   Cambia la contraseña de ejemplo por la contraseña de MariaDB que configuraste para `fletetonelada_app`.

5. Protege el archivo privado para que Apache lo pueda leer y Git no lo publique:

   ```bash
   sudo chown bluberi:www-data config/db.php
   sudo chmod 640 config/db.php
   ```

   Ajusta `bluberi` si tu usuario de Debian tiene otro nombre. El archivo `/config/db.php` está excluido por `.gitignore`; nunca lo agregues manualmente a Git.

6. Abre en el navegador:

   `http://localhost/fletetonelada/`

## Cuentas de demostración

El archivo `sql/seed.sql` incluye tres cuentas académicas. La contraseña temporal de las tres es `FleteDemo2026!`.

| Rol | Correo |
|---|---|
| Cliente | `cliente@fletetonelada.test` |
| Transportista | `transportista@fletetonelada.test` |
| Administrador | `admin@fletetonelada.test` |

Cambia estas contraseñas antes de usar datos reales. Los transportistas de demostración están verificados para permitir probar la aceptación de cargas. Los transportistas creados mediante el formulario público quedan pendientes de aprobación.

## Flujo de demostración

1. En la página pública, cotiza un Tortón de 10 toneladas entre Puebla y Ciudad de México.
2. Inicia sesión como cliente y publica una carga con direcciones de prueba.
3. Inicia sesión como transportista, registra una unidad Tortón y acepta la carga.
4. Consulta `Mis viajes`, registra la recogida y marca el viaje en tránsito.
5. Desde la cuenta del cliente, consulta el detalle y comparte el PIN de seis dígitos.
6. Captura el PIN en `Mis viajes` del transportista. El sistema marca la carga como entregada y el pago simulado como liberado.
7. Califica al transportista desde la cuenta del cliente.
8. Inicia sesión como administrador para verificar transportistas y consultar cargas.

Para mantener dos sesiones abiertas durante la demostración, utiliza una ventana normal y otra de incógnito, o dos perfiles distintos del navegador.

## Reglas y seguridad implementadas

- Contraseñas con `password_hash()` y validación con `password_verify()`.
- Sesiones PHP con regeneración del identificador al iniciar sesión y cookies `HttpOnly`.
- Control de acceso por rol en el servidor.
- Formularios que modifican datos protegidos con token CSRF.
- Consultas PDO preparadas y salida HTML escapada.
- El servidor vuelve a calcular precio, distancia y comisión; no confía en precios del navegador.
- Las cargas solo se muestran a transportistas con una unidad activa del tipo requerido.
- La aceptación se ejecuta dentro de una transacción y actualiza la carga solo si continúa publicada.
- Direcciones exactas y contactos se revelan después de la asignación y únicamente a las partes involucradas.
- El PIN solo se muestra al cliente; se permiten cinco intentos.
- La confirmación por PIN actualiza entrega, carga y pago simulado dentro de una transacción.
- Una carga solo puede tener un viaje y un registro de pago.
- El historial registra los cambios de estado.

## Limitaciones del MVP

- Los pagos son simulados; no se conecta una pasarela ni se mueve dinero real.
- No se genera CFDI ni Carta Porte oficial.
- No hay GPS en tiempo real ni integración con mapas de terceros.
- No se desarrolla aplicación móvil nativa ni envío de SMS/correos.
- La distancia es una aproximación por Haversine multiplicada por 1.25; no equivale a una ruta de carretera calculada por un servicio cartográfico.
- La aplicación es una base académica y requiere una auditoría adicional antes de un despliegue público.

## Pruebas básicas

Revisa sintaxis PHP con:

```bash
find . -path './.git' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
```

Comprueba que el servidor tenga las extensiones necesarias:

```bash
php -m | grep -Ei 'PDO|pdo_mysql|mbstring|session'
```

Comprueba la base de datos:

```bash
sudo mariadb -e "USE fletetonelada; SHOW TABLES;"
```

## Documentación académica

Los archivos LaTeX, diagramas, manuales y contrato se preparan en un proyecto independiente, fuera de esta carpeta de aplicación, según la organización acordada. No se incluyen archivos `.tex` ni PDF en este repositorio.
