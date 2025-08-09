// Utilidades
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

// Menu responsive
const navToggle = $('.nav__toggle');
const navMenu = $('#navMenu');
navToggle?.addEventListener('click', () => {
  const isOpen = navMenu.classList.toggle('open');
  navToggle.setAttribute('aria-expanded', String(isOpen));
});
$$('.nav__link').forEach(l => l.addEventListener('click', () => {
  if (navMenu.classList.contains('open')) {
    navMenu.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  }
}));

// Render de productos
const productsGrid = $('#productsGrid');
function formatPrice(num) { return new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(num); }
function createProductCard(p) {
  const el = document.createElement('article');
  el.className = 'product';
  el.innerHTML = `
    <img class="product__img" src="${p.img}" alt="${p.title}" loading="lazy"/>
    <div class="product__body">
      <h3 class="product__title">${p.title}</h3>
      <div class="product__price">${formatPrice(p.price)}</div>
      <div class="product__tags">${p.tags.map(t => `<span class='tag'>${t}</span>`).join('')}</div>
    </div>
    <div class="product__actions">
      <button class="btn btn--outline" data-action="wish" aria-label="Agregar a favoritos">❤ Favorito</button>
      <button class="btn btn--primary" data-action="buy" aria-label="Agregar al carrito">🛒 Agregar</button>
    </div>`;
  return el;
}
function renderProducts() {
  if (!productsGrid) return;
  productsGrid.innerHTML = '';
  PRODUCTS.forEach(p => productsGrid.appendChild(createProductCard(p)));
}

// Slider de testimonios simple
const slider = $('#testimonialSlider');
function renderTestimonials() {
  if (!slider) return;
  slider.innerHTML = '';
  TESTIMONIALS.forEach(t => {
    const card = document.createElement('article');
    card.className = 'testimonial';
    card.innerHTML = `<p>“${t.text}”</p><div class="testimonial__name">${t.name}</div>`;
    slider.appendChild(card);
  });
}

// Back to top
const backTop = $('#backToTop');
window.addEventListener('scroll', () => {
  if (window.scrollY > 400) backTop.classList.add('show');
  else backTop.classList.remove('show');
});
backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

// Año dinámico
$('#year').textContent = new Date().getFullYear();

// Validación básica del formulario
const form = $('#contactForm');
function showError(input, msg) {
  const field = input.closest('.form__field');
  const small = field?.querySelector('.error');
  if (small) small.textContent = msg || '';
}
function validateEmail(email) {
  return /\S+@\S+\.\S+/.test(email);
}
form?.addEventListener('submit', (e) => {
  e.preventDefault();
  const name = $('#name');
  const email = $('#email');
  const message = $('#message');
  let ok = true;

  if (!name.value.trim()) { showError(name, 'Ingresa tu nombre'); ok = false; } else showError(name, '');
  if (!validateEmail(email.value)) { showError(email, 'Correo inválido'); ok = false; } else showError(email, '');
  if (!message.value.trim()) { showError(message, 'Escribe un mensaje'); ok = false; } else showError(message, '');

  if (ok) {
    const msg = `Hola, soy ${name.value.trim()} (${email.value.trim()}).\nQuiero información:\n${message.value.trim()}`;
    const link = buildWaLink({ text: msg });
    window.open(link, '_blank');
  }
});

// WhatsApp floating button and form submission
const WA_NUMBER = '+50255555555'; // Cambia por tu número con código de país
const waBtn = document.getElementById('waButton');
function buildWaLink(params) {
  const base = 'https://wa.me/';
  const text = encodeURIComponent(params.text || 'Hola, quiero más información.');
  return `${base}${WA_NUMBER.replace(/[^\d]/g,'')}?text=${text}`;
}
waBtn?.addEventListener('click', () => {
  const link = buildWaLink({ text: 'Hola Paso Firme, me gustaría consultar disponibilidad.' });
  window.open(link, '_blank');
});

// Init
renderProducts();
renderTestimonials();
