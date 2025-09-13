# Calzado Oxlaj
Sitio web para la zapatería Calzado Oxlaj con catálogo, carrito, favoritos, testimonios y contacto (Gmail Compose / mailto) más botón directo de WhatsApp. Actualmente el sitio está en **modo servidor** (`USE_SERVER = true`). Si la conexión a la base de datos falla (por ejemplo, MySQL no levantado) el frontend hace **fallback automático** a modo offline para que el sitio siga funcionando con datos locales.

## Características (modo servidor activo)
- Catálogo desde BD MySQL (endpoint `api/products.php`)
- CRUD inline (ADMIN) guardando en tablas `productos`, `tags`, `producto_tags`
- Favoritos vinculados a usuario autenticado (`api/favoritos.php`)
- Carrito mediante endpoint (`api/cart.php`) - actualmente global (no multiusuario)
- Testimonios desde BD (`api/testimonios.php`)
- Contacto: abre Gmail Compose (o mailto) con copia BCC
- Botón rápido de WhatsApp
- Diseño responsivo
- Fallback local manual cambiando `USE_SERVER=false`
- Fallback automático si hay error de conexión BD (mensaje: "No se pudo conectar a la base de datos")

## Autenticación y roles
Modo actual: autenticación real usando `api/auth.php` (correo + contraseña que se verifica contra `usuarios.password_hash`).
El formulario muestra campo de correo automáticamente al estar `USE_SERVER=true`.
Si cambias a modo offline (`USE_SERVER=false`), vuelve a contraseñas simples locales.

### Usuario administrador (BD)
Inserta un admin si aún no existe (ejemplo):
```sql
INSERT INTO usuarios (nombre, correo, password_hash, rol)
VALUES ('Admin','admin@demo.test', '$2y$10$DEMO_REEMPLAZAR_HASH', 'admin');
```
Genera el hash en consola PHP: `php -r "echo password_hash('admin123', PASSWORD_BCRYPT), PHP_EOL;"`

### Fallback contraseñas locales (solo offline)
- Cliente: `cliente123`
- Administrador: `admin123`
Se editan en `ROLE_PASSWORDS` (`main.js`).

### Backend (opcional desactivado)
Existe un backend normalizado en `api/` (productos, tags, usuarios, favoritos, carrito, testimonios). No se está usando porque `USE_SERVER=false`.

## Estructura
- `index.html`: página principal (catálogo público, login de roles)
   (CRUD inline reemplaza al antiguo panel separado eliminado del repositorio.)
- `assets/css/styles.css`: estilos
- `assets/js/data.js`: datos de fallback (modo offline)
- `assets/js/main.js`: lógica UI (productos, CRUD, carrito, favoritos, auth, testimonios)
- `assets/img/`: imágenes y logo
- `docs/`: manuales técnico y de usuario
- `netlify.toml`: configuración de despliegue

## Puesta en marcha (modo servidor)
1. Clonar repositorio:
   ```bash
   git clone https://github.com/Oxlaj/zapateria-paso-firme.git
   cd zapateria-paso-firme
   ```
2. Crear BD y tablas:
   ```bash
   mysql -u root -p < api/schema.sql
   ```
3. Crear `api/config.local.php` (si tus credenciales difieren):
   ```php
   <?php
   $DB_HOST='127.0.0.1';
   $DB_USER='root';
   $DB_PASS='admin'; // ajusta
   $DB_NAME='calzado_oxlaj';
   ```
4. Insertar usuario admin (ver sección anterior).
5. Servir con PHP embebido (para sesiones):
   ```bash
   php -S 127.0.0.1:8080
   ```
6. Abrir http://127.0.0.1:8080/ y hacer login con correo + password.
7. CRUD admin ahora persiste en la base de datos.

### Modo offline (fallback)
Manual: cambia `USE_SERVER = false` en `main.js`. Se ocultará el campo correo y volverán las contraseñas locales.

Automático: si el frontend detecta error recurrente al pedir `api/products.php` (texto *"conectar a la base de datos"*) desactiva modo servidor en la sesión y renderiza con datos locales.

Diagnóstico rápido BD: visitar `api/test_db.php` mostrará conexión, tablas presentes y conteos básicos.

## Endpoints principales
| Recurso | Método(s) | Endpoint | Descripción |
|---------|-----------|----------|-------------|
| Productos | GET | `api/products.php` | Lista productos + tags |
| CRUD Productos | POST / PUT / DELETE | `api/admin_products.php` | Administrar productos (admin) |
| Carrito | GET / POST / PUT / DELETE | `api/cart.php` | Estado y operaciones de carrito |
| Favoritos | GET / POST / DELETE | `api/favoritos.php` | Gestiona favoritos del usuario |
| Testimonios | GET | `api/testimonios.php` | Lista testimonios |
| Autenticación | GET / POST / DELETE | `api/auth.php` | Sesión (obtener / login / logout) |

## Personalización
- Para agregar productos iniciales con imágenes propias de forma permanente (semilla), coloca los archivos en `assets/img/catalogo/` y edita `assets/js/data.js`.
- Para agregar o cambiar imágenes desde el modo ADMIN usa el área de drag & drop (no requiere URL). La imagen queda embebida (Data URL) y persiste solo en tu navegador.
- Cambia el número de WhatsApp en `main.js` (`WA_NUMBER`).
- Cambia el correo BCC (del sitio) editando la constante `siteMail` en el handler del formulario. El destinatario principal siempre es el email que escribe el usuario.
 - Imagen principal (hero): ahora usa `<picture>` con soporte opcional para `portada.avif` y `portada.webp`. Si solo tienes `portada.jpg`, el sitio funciona igual (los `<source>` fallan en silencio). Para generar versiones optimizadas puedes usar:
    - Squoosh (web) o `cwebp`: `cwebp -q 82 portada.jpg -o portada.webp`
    - `avifenc`: `avifenc --min 30 --max 35 portada.jpg portada.avif`
    Coloca los archivos en `assets/img/` junto al original.

## Despliegue
- Estático (Netlify / GitHub Pages): usar `USE_SERVER=false`.
- Dinámico (Hostinger / cPanel / VPS): subir todo, configurar `config.local.php` o variables de entorno, `USE_SERVER=true` y asegurar soporte PHP >= 8 + MySQL.

## Manuales
- [Manual Técnico (RTF)](docs/Manual_Tecnico_Calzado_Oxlaj.rtf)
- [Manual de Usuario (RTF)](docs/Manual_de_Usuario_Calzado_Oxlaj.rtf)

## Soporte
Formulario (Gmail / mailto) o WhatsApp. Para soporte técnico crear Issue en GitHub o contactar al correo BCC.
