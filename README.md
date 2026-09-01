# QWEB — Portal de consultas registrales

Portal web que reúne las consultas que el personal de una zona registral hacía
en cinco aplicaciones de escritorio distintas, más el autoservicio para
restablecer la contraseña de los sistemas registrales.

---

## Por qué existe

Las cinco aplicaciones eran de escritorio y solo funcionaban en **Windows XP y
Windows 7**. Cada equipo que se renovaba dejaba de poder usarlas, y mantenerlas
significaba mantener también las máquinas viejas que las hacían andar.

El portal las reimplementa como una sola aplicación web contra las mismas bases
Oracle. Se entra desde cualquier navegador, no hay nada que instalar en el
equipo del usuario, y la renovación del parque dejó de ser un problema.

De paso resolvió el ticket más repetido de la mesa de ayuda: **cambiar la
contraseña**. Antes había que llamar a soporte; ahora la persona valida su
identidad por correo institucional y la cambia sola.

---

## Qué incluye

| Módulo | Qué resuelve |
|---|---|
| Pendiente de Devoluciones | Títulos pendientes de devolver al usuario. |
| Consulta Vehicular Nacional | Consulta del registro vehicular a nivel nacional. |
| Sistema BUS-IND | Búsqueda de índices. |
| Títulos Pendientes de Firma Digital | Qué falta firmar y desde cuándo. |
| Índice de Partidas (PROP) | Búsqueda de partidas por ficha, tomo, folio o nombre. |
| Cambio de contraseña | Autoservicio, validando identidad por correo institucional. |

---

## Decisiones que vale la pena mirar

**La autenticación es la propia base de datos.** No hay tabla de usuarios ni
hash de contraseñas que mantener: el portal intenta conectarse a Oracle con la
cuenta que la persona escribió
([`control/validar_usuario.php`](control/validar_usuario.php)). Si Oracle acepta,
es quien dice ser. Si no, el error se traduce a algo que el usuario entienda:
`ORA-01017` pasa a «usuario o contraseña incorrectos» y `ORA-28000` a «la cuenta
está bloqueada, contacta al administrador», en vez de mostrar el código crudo.

Esa traducción salió de la mesa de ayuda: eran exactamente los dos casos que
generaban llamadas, y distinguirlos en pantalla evita el ticket.

**El cambio de contraseña no lo hace el PHP.** Llama al procedimiento
`pkg_qweb.sp_recover_passwd` ([`vista/cambio_clave.php`](vista/cambio_clave.php)),
que vive en la base. La lógica de qué es una contraseña válida y cómo se aplica
está donde están las cuentas, no repartida en cada aplicación que quiera
cambiarlas.

**El índice de partidas es un SQLite aparte.** La búsqueda por nombre sobre la
tabla de partidas en Oracle era lenta para un uso interactivo, así que se genera
un índice local de solo lectura y las consultas van contra él, con sentencias
preparadas ([`modelo/prop_datos.php`](modelo/prop_datos.php)). La base Oracle
sigue siendo la fuente de verdad; el SQLite es solo una copia para leer rápido.

**Una base por oficina.** Cada oficina registral tiene la suya, y el portal
resuelve a cuál conectarse según lo que se consulte. Las cuatro se declaran en el
entorno, no en el código.

---

## Puesta en marcha

Requiere PHP con la extensión OCI8 y acceso a las bases Oracle.

```bash
cp .env.example .env
```

Declara las oficinas en `QWEB_OFICINAS` y una variable `QWEB_ORACLE_<OFICINA>`
por cada una, con formato `HOST:PUERTO/SID`. Completa también el usuario de
consulta de solo lectura.

Apunta el servidor web a la raíz del proyecto.

---

## Alcance y limitaciones

- **El repositorio no arranca solo.** Depende del paquete PL/SQL `pkg_qweb`, de
  los enlaces de base de datos entre oficinas y del esquema registral, que viven
  en Oracle y no aquí.
- **El índice de partidas no está incluido.** Son datos registrales de personas
  reales. `PROP_SQLITE_PATH` apunta a dónde dejarlo; el proceso que lo genera
  desde Oracle tampoco forma parte de este repositorio.
- **La contraseña de Oracle se guarda en la sesión de PHP** mientras dura, porque
  cada petición vuelve a conectarse con la cuenta de la persona. Sin un pool de
  conexiones no hay dónde mantenerla viva. Es el pendiente más importante.
- **Sin token CSRF** en los formularios. El portal corría en la intranet, lo que
  lo mitiga pero no lo justifica.
- **Sin límite de intentos en el login.** El bloqueo de cuenta lo aplica Oracle,
  no la aplicación.

---

## Contexto

Lo construí en 2026 durante mis prácticas en la Unidad de Tecnologías de la
Información de SUNARP (Zona Registral N° XI). Estuvo en producción en las cuatro
oficinas registrales de la zona.

Este repositorio es una versión limpia: sin el índice de partidas, sin las
credenciales de conexión y sin la red interna. Publicado con autorización del
área.
