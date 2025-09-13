# Calzado Oxlaj
Sitio web para la zapatería Calzado Oxlaj con catálogo, carrito, favoritos, testimonios y contacto (Gmail Compose / mailto) más botón directo de WhatsApp. El modo **servidor (PHP + MySQL)** está ACTIVO por defecto (`USE_SERVER = true`) y el frontend consume los endpoints en `api/`.

## Características
- Catálogo de productos desde MySQL (tags normalizados)
- CRUD inline (solo ADMIN) persistente en BD (crear / editar / eliminar + drag & drop de imagen)
- Carrito persistente (tabla `carrito`) – actualmente global (pendiente multiusuario real)
- Favoritos por usuario autenticado (`favoritos.php`) con fallback a localStorage si no hay sesión
- Testimonios desde tabla `testimonios`
- Contacto: formulario que abre Gmail Compose dirigido al email ingresado y agrega **vieryoxlaj8@gmail.com** en BCC (fallback mailto)
- Botón flotante de WhatsApp para mensaje rápido
- Búsqueda por nombre (modo ADMIN)
- Diseño responsivo y accesible
- Despliegue estático (modo offline) o dinámico (PHP) en hosting compartido / Hostinger / Apache / Nginx

## Autenticación y roles
Dos modos:
1. OFFLINE / DEMO (`USE_SERVER=false`): selector de rol (Cliente / Administrador) con contraseñas locales.
2. SERVIDOR (`USE_SERVER=true`): login real (correo + password) vía `api/auth.php`. El CRUD requiere rol `admin` (ver tabla `usuarios`).

Sincronizados en modo servidor:
- Productos (`products.php`, `admin_products.php`)
- Carrito (`cart.php`)
- Favoritos (`favoritos.php`)
- Testimonios (`testimonios.php`)

La interfaz de CRUD inline (drag & drop, búsqueda, edición rápida) funciona igual en ambos modos; la diferencia es la persistencia (localStorage vs BD).

### Contraseñas (modo offline)
Solo aplican si `USE_SERVER=false`:
- Cliente: `cliente123`
- Administrador: `admin123`
Editar en el objeto `ROLE_PASSWORDS` dentro de `assets/js/main.js`.

### Backend normalizado
Tablas principales: `productos`, `tags`, `producto_tags`, `testimonios`, `usuarios`, `favoritos`, `carrito` (y otras del script ampliado si se usa). El frontend ya consume estos recursos.

## Estructura
- `index.html`: página principal (catálogo público, login de roles)
   (CRUD inline reemplaza al antiguo panel separado eliminado del repositorio.)
- `assets/css/styles.css`: estilos
- `assets/js/data.js`: datos de fallback (modo offline)
- `assets/js/main.js`: lógica UI (productos, CRUD, carrito, favoritos, auth, testimonios)
- `assets/img/`: imágenes y logo
- `docs/`: manuales técnico y de usuario
- `netlify.toml`: configuración de despliegue

## Puesta en marcha (modo servidor por defecto)
1. Clonar repositorio:
   ```bash
   git clone https://github.com/Oxlaj/zapateria-paso-firme.git
   cd zapateria-paso-firme
   ```
2. Crear BD y ejecutar el script SQL normalizado.
3. Copiar `api/config.local.php.example` a `api/config.local.php` y ajustar credenciales.
4. (Opcional) Insertar usuario admin:
   ```sql
   INSERT INTO usuarios (nombre,correo,password_hash,rol) VALUES ('Admin','admin@example.com', PASSWORD_HASH_AQUI, 'admin');
   ```
   Generar hash en PHP:
   ```php
   <?php echo password_hash('TuPasswordSeguro', PASSWORD_BCRYPT); ?>
   ```
5. Iniciar servidor PHP local:
   ```bash
   php -S 127.0.0.1:8000
   ```
6. Abrir `http://127.0.0.1:8000` en el navegador. El sitio consumirá la BD.

### Cambiar a modo offline
Editar `assets/js/main.js` y poner `const USE_SERVER = false;` (productos, carrito y favoritos vuelven a localStorage, login vuelve a roles estáticos).

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
- Estático (Netlify / GitHub Pages): fijar `USE_SERVER=false`.
- Dinámico (Hostinger / cPanel / VPS): subir todo, configurar `config.local.php` o variables de entorno y listo (ya `USE_SERVER=true`).

## Manuales
- [Manual Técnico (RTF)](docs/Manual_Tecnico_Calzado_Oxlaj.rtf)
- [Manual de Usuario (RTF)](docs/Manual_de_Usuario_Calzado_Oxlaj.rtf)

## Soporte
Formulario (Gmail / mailto) o WhatsApp. Para soporte técnico crear Issue en GitHub o contactar al correo BCC.
