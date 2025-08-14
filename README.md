# Calzado Oxlaj (sitio estático)

Sitio web estático en HTML5, CSS3 y JavaScript para una zapatería.

## Estructura
- index.html
- assets/
  - css/styles.css
  - js/data.js
  - js/main.js
  - img/
- netlify.toml (configuración de despliegue)

## Ver el sitio
- Opción rápida: abre `index.html` en tu navegador.
- Recomendado en VS Code: instala "Live Server" y clic en "Go Live".

## Personalizar
- Nombre/branding: textos en `index.html` (actual: "Calzado Oxlaj").
- Colores: variables en `assets/css/styles.css` (`--primary`, `--primary-600`).
- Productos/opiniones: `assets/js/data.js`.
- Imágenes: coloca archivos en `assets/img/` y referencia en `data.js`.

---

## Manual técnico

### 1. Requisitos
- Git 2.x
- VS Code (sugerido) + Live Server (sugerido)
- Navegador moderno

### 2. Arquitectura
- HTML semántico en `index.html`.
- CSS modular por secciones en `assets/css/styles.css` (variables CSS para temas).
- JS vanila en `assets/js/`:
  - `data.js`: datos del catálogo y testimonios.
  - `main.js`: UI (menú, render catálogo, validación, WhatsApp, back-to-top).

### 3. Variables y temas
- Paleta principal: azul marino
  - `--primary: #0b3d91`
  - `--primary-600: #092f6b`
- Ajusta tipografías o contenedor en `:root`.

### 4. Catálogo (data.js)
- Estructura de producto:
  - `{ id: number, title: string, price: number, img: string, tags: string[] }`
- Para usar fotos propias: `img: 'assets/img/mi-foto.jpg'` y coloca el archivo en `assets/img/`.

### 5. Accesibilidad
- Navegación con aria-labels y `aria-live` en testimonios.
- Botones y enlaces con descripciones.

### 6. Seguridad
- No hay backend ni formularios que envíen datos a servidores. El botón de WhatsApp abre wa.me.

### 7. Despliegue
- Es un sitio estático: servir desde raíz (`/`).
- `netlify.toml` define publish = "." (carpeta raíz).

---

## Manual de usuario (operación del sitio)
- Menú: usa el icono ☰ en móvil.
- Catálogo: navega los productos destacados; los botones son demostrativos.
- Opiniones: sección con testimonios.
- Contacto: completa el formulario; abrirá WhatsApp con tu mensaje.
- Botón WhatsApp: ícono verde flotante para iniciar chat directo.

---

## Guía: subir a GitHub
1) Crear repo (si no existe):
   - Con GitHub CLI: `gh repo create zapateria-paso-firme --public --source . --push`
2) Commits y push posteriores:
   - `git add .`
   - `git commit -m "feat: cambio X"`
   - `git push origin main`

> En este proyecto ya está configurado `origin` y se trabaja en `main`.

## Guía: desplegar en Netlify
Opción A — Conectar tu repo (recomendado):
1) En Netlify, "Add new site" > "Import an existing project".
2) Elige GitHub y selecciona `Oxlaj/zapateria-paso-firme`.
3) Build command: vacío (es estático). Publish directory: `.`
4) Deploy. Netlify asignará un dominio `*.netlify.app`.
5) En cada `git push`, Netlify redeploya automáticamente.

Opción B — Deploy manual por carpeta:
1) En Netlify, "Add new site" > "Deploy manually".
2) Arrastra la carpeta del proyecto (contenido con `index.html`).

Configuración opcional:
- netlify.toml ya establece `publish = "."`.
- Si añades más páginas o SPA, puedes usar redirects.

## Solución de problemas
- Imágenes no cargan: revisa la ruta `assets/img/nombre.jpg` y que el archivo exista.
- WhatsApp no abre: reemplaza `WA_NUMBER` en `assets/js/main.js` por tu número internacional (+502...).
- Cambios no se ven en Netlify: confirma que hiciste `git push` a `main`.

---

## Licencia
Uso personal/educativo. Ajusta según tu necesidad.
