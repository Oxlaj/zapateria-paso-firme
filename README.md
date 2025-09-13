# Calzado Oxlaj
Sitio web para la zapatería Calzado Oxlaj con catálogo, carrito, favoritos, testimonios y contacto (Gmail Compose / mailto) más botón directo de WhatsApp. Actualmente el sitio está en **modo offline** (`USE_SERVER = false`) usando solo datos locales y roles con contraseña simple.

## Características (modo offline actual)
- Catálogo base definido en `assets/js/data.js`
- CRUD inline (solo ADMIN) persistente en tu navegador (localStorage)
- Carrito y favoritos en localStorage
- Búsqueda por nombre (modo ADMIN)
- Contacto: abre Gmail Compose (o mailto) con copia BCC
- Botón rápido de WhatsApp
- Diseño responsivo
- Posibilidad de activar modo servidor más adelante (`USE_SERVER=true`)

## Autenticación y roles
Modo actual: solo selector local (Cliente / Administrador) con contraseñas simples.
Si se activa el modo servidor (`USE_SERVER=true`) volverán los endpoints y autenticación real.

### Contraseñas
- Cliente: `cliente123`
- Administrador: `admin123`
Editar en `ROLE_PASSWORDS` dentro de `assets/js/main.js`.

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

## Puesta en marcha (modo offline actual)
1. Clonar repositorio:
   ```bash
   git clone https://github.com/Oxlaj/zapateria-paso-firme.git
   cd zapateria-paso-firme
   ```
2. Abrir `index.html` directamente en el navegador o usar un servidor simple (Live Server / php -S). No requiere BD.
3. Elegir rol, ingresar la contraseña local y administrar.

### Activar modo servidor (opcional)
1. Configurar BD con `api/schema.sql`.
2. Crear `api/config.local.php` con credenciales.
3. Cambiar en `assets/js/main.js` a `const USE_SERVER = true;`.
4. Recargar: ahora CRUD y datos irán contra la BD.

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
