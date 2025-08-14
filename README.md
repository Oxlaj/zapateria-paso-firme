# Calzado Oxlaj (sitio estático)

Sitio web estático en HTML5, CSS3 y JavaScript para una zapatería.

## Estructura
- index.html
- assets/
  - css/styles.css
  - js/data.js
  - js/main.js
  - img/ (coloca aquí tus imágenes reales si no usas los placeholders)

## Ver el sitio
- Opción rápida: abre el archivo `index.html` en tu navegador.
- Recomendado en VS Code: instala la extensión "Live Server" y haz clic en "Go Live" para recargar cambios al guardar.

## Personalizar
- Nombre/branding: cambia textos en `index.html` (actual: "Calzado Oxlaj").
- Colores: ajusta variables en `assets/css/styles.css` (por ejemplo `--primary` y `--primary-600`, definidos en azul marino).
- Productos y opiniones: edita `assets/js/data.js`.
- Imágenes: por defecto se usan placeholders remotos. Sustitúyelos por rutas locales (por ejemplo `assets/img/hero.jpg`) y coloca las imágenes en `assets/img/`.

## Accesibilidad y rendimiento
- Navegación responsiva con menú accesible.
- Imágenes con `loading="lazy"` y tipografías optimizadas.

## Próximos pasos (opcionales)
- Agregar formulario real (Email/WhatsApp/Google Forms).
- SEO/OG: título/descrición ya listos; puedes añadir Open Graph y favicon propio.
- Multi–página (catálogo, detalle de producto) si lo necesitas.
