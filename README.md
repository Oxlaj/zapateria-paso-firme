# Calzado Oxlaj
Sitio web estático para la zapatería Calzado Oxlaj. Catálogo, favoritos y contacto por WhatsApp. Sin base de datos ni autenticación (deshabilitados temporalmente).

## Características
- Catálogo de productos (imágenes locales y de muestra)
- Marcar productos como favoritos (se guarda en tu dispositivo)
- Contacto y pedidos por WhatsApp (botón flotante y formulario)
- Testimonios y sección de información
- Diseño responsivo y accesible
- Despliegue en Netlify y GitHub Pages

## Roles estáticos y Panel Admin
- Al abrir el sitio se muestra primero un selector de rol (Cliente o Administrador).
- La elección se guarda en localStorage y se puede cambiar con “Cerrar sesión”.
- Si eliges Administrador, en la sección “Productos” verás un “Panel Administrador (estático)”.
   - Permite crear/editar/eliminar productos en memoria y se guardan en localStorage.
   - Campos: ID, Título, Precio, Imagen (ruta como assets/img/...), Etiquetas (separadas por comas).
   - Para resetear los cambios de productos, borra el almacenamiento del navegador (localStorage) o cambia de navegador.

### Contraseñas de roles
Se validan de forma local (solo frontend, no seguro para producción):
- Cliente: `cliente123`
- Administrador: `admin123`

Cómo cambiarlas: editar el objeto `ROLE_PASSWORDS` en `assets/js/main.js`.

Nota: El backend en PHP/MySQL existe en la carpeta `api/`, pero está deshabilitado desde el frontend (USE_SERVER=false).

## Estructura
- `index.html`: página principal
- `assets/css/styles.css`: estilos
- `assets/js/data.js`: datos de productos y testimonios
- `assets/js/main.js`: lógica de favoritos, WhatsApp, renderizado (sin backend; USE_SERVER=false)
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
3. Marca favoritos y contacta por WhatsApp.

## Personalización
- Para agregar productos con imágenes propias, coloca los archivos en `assets/img/catalogo/` y edita `assets/js/data.js`.
- Cambia el número de WhatsApp en `main.js` (`WA_NUMBER`).

## Despliegue
- Netlify: conecta el repo y publica desde el directorio raíz (`publish = "."`).
- GitHub Pages: activa Pages en la rama `main`.

## Manuales
- [Manual Técnico (RTF)](docs/Manual_Tecnico_Calzado_Oxlaj.rtf)
- [Manual de Usuario (RTF)](docs/Manual_de_Usuario_Calzado_Oxlaj.rtf)

## Soporte
Si tienes dudas o problemas, usa el botón de WhatsApp para contactarnos.
