# Calzado Oxlaj
Sitio web estático para la zapatería Calzado Oxlaj. Catálogo, favoritos y contacto directo abriendo Gmail (o fallback mailto) y botón rápido de WhatsApp. El backend PHP/MySQL en `api/` quedó como referencia histórica y ya no se invoca desde el frontend.

## Características
- Catálogo de productos (imágenes locales y de muestra)
- Marcar productos como favoritos (se guarda en tu dispositivo)
- Contacto: formulario que abre Gmail Compose dirigido al correo ingresado por el usuario y agrega **vieryoxlaj8@gmail.com** en BCC (si Gmail no está disponible se usa mailto)
- Botón flotante de WhatsApp para mensaje rápido
- Testimonios y sección de información
- Diseño responsivo y accesible
- Despliegue en Netlify y GitHub Pages
 - Búsqueda (solo modo ADMIN) por nombre de producto

## Roles estáticos y administración inline
- Al abrir el sitio (`index.html`) aparece un selector de rol (Cliente o Administrador).
- La elección se guarda en localStorage y se puede cambiar con “Cerrar sesión”.
- En modo ADMIN el catálogo se vuelve editable directamente (CRUD inline). Se ocultan funciones de compra y carrito.
   - Acciones: crear, editar y eliminar productos de forma inline.
   - Carga de imagen local mediante drag & drop o botón “Selecciona” (se guarda como Data URL en localStorage).
   - Campo de búsqueda (solo nombre) para filtrar productos localmente mientras se administra.
   - Los cambios viven solo en este navegador mediante la clave `oxlaj_products_override`.
   - Para reiniciar el catálogo: borrar la clave en localStorage o limpiar datos desde la consola.

### Contraseñas de roles
Se validan de forma local (solo frontend, no seguro para producción):
- Cliente: `cliente123`
- Administrador: `admin123`

Cómo cambiarlas: editar el objeto `ROLE_PASSWORDS` en `assets/js/main.js`.

Nota: El backend en PHP/MySQL existe en la carpeta `api/`, pero el frontend funciona 100% local (sin llamadas de red para carrito ni CRUD).

## Estructura
- `index.html`: página principal (catálogo público, login de roles)
   (CRUD inline reemplaza al antiguo panel separado eliminado del repositorio.)
- `assets/css/styles.css`: estilos
- `assets/js/data.js`: datos de productos y testimonios
- `assets/js/main.js`: lógica de favoritos, renderizado, carrito local, formulario vía Gmail Compose (sin backend)
- `assets/img/`: imágenes y logo
- `docs/`: manuales técnico y de usuario
- `netlify.toml`: configuración de despliegue

## Cómo usar
1. Clona el repositorio:
   ```sh
   git clone https://github.com/Oxlaj/zapateria-paso-firme.git
   cd zapateria-paso-firme
   ```
2. Abre `index.html` en tu navegador, o usa Live Server en VS Code.
3. Marca favoritos y contacta por correo (formulario) o WhatsApp (botón verde).

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
- Netlify: conecta el repo y publica desde el directorio raíz (`publish = "."`).
- GitHub Pages: activa Pages en la rama `main`.

## Manuales
- [Manual Técnico (RTF)](docs/Manual_Tecnico_Calzado_Oxlaj.rtf)
- [Manual de Usuario (RTF)](docs/Manual_de_Usuario_Calzado_Oxlaj.rtf)

## Soporte
Puedes escribir desde el formulario (abre Gmail o tu cliente de correo) o usar el botón de WhatsApp para contactarnos.
