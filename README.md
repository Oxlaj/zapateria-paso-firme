# Calzado Oxlaj
Sitio web estático para la zapatería Calzado Oxlaj. Catálogo, favoritos y contacto por WhatsApp. Sin carrito de compras ni mapa.

## Características
- Catálogo de productos (imágenes locales y de muestra)
- Marcar productos como favoritos (se guarda en tu dispositivo)
- Contacto y pedidos por WhatsApp (botón flotante y formulario)
- Testimonios y sección de información
- Diseño responsivo y accesible
- Despliegue en Netlify y GitHub Pages

## Estructura
- `index.html`: página principal
- `assets/css/styles.css`: estilos
- `assets/js/data.js`: datos de productos y testimonios
- `assets/js/main.js`: lógica de favoritos, WhatsApp, renderizado
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
