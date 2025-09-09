// Datos simulados de productos y opiniones
const PRODUCTS = [
  { id: 1, title: 'Zapatilla Runner Pro', price: 459.99, img: 'https://placehold.co/600x450/jpg?text=Runner+Pro', tags: ['Deportivo', 'Hombre'] },
  { id: 2, title: 'Botín Urbano', price: 549.50, img: 'https://placehold.co/600x450/jpg?text=Bot%C3%ADn+Urbano', tags: ['Casual', 'Mujer'] },
  { id: 3, title: 'Sandalia Confort', price: 229.00, img: 'https://placehold.co/600x450/jpg?text=Sandalia+Confort', tags: ['Verano', 'Mujer'] },
  { id: 4, title: 'Mocasín Clásico', price: 379.00, img: 'https://placehold.co/600x450/jpg?text=Mocas%C3%ADn+Cl%C3%A1sico', tags: ['Formal', 'Hombre'] },
  { id: 5, title: 'Tenis Kids Color', price: 199.99, img: 'https://placehold.co/600x450/jpg?text=Kids+Color', tags: ['Niños', 'Deportivo'] },
  { id: 6, title: 'Bota Trekking', price: 629.00, img: 'https://placehold.co/600x450/jpg?text=Bota+Trekking', tags: ['Outdoor', 'Unisex'] },
  { id: 7, title: 'Flat Elegante', price: 259.00, img: 'https://placehold.co/600x450/jpg?text=Flat+Elegante', tags: ['Casual', 'Mujer'] },
  { id: 8, title: 'Oxford Premium', price: 699.00, img: 'https://placehold.co/600x450/jpg?text=Oxford+Premium', tags: ['Formal', 'Hombre'] },
  // Nuevos del folder catalogo
  { id: 9, title: 'Modelo A', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_efa395bc.jpg', tags: ['Casual'] },
  { id: 10, title: 'Modelo B', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.20_ffb7cb86.jpg', tags: ['Casual'] },
  { id: 11, title: 'Modelo C', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_2fc74464.jpg', tags: ['Casual'] },
  { id: 12, title: 'Modelo D', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_57dfcde9.jpg', tags: ['Casual'] },
  { id: 13, title: 'Modelo E', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_69ed2281.jpg', tags: ['Casual'] },
  { id: 14, title: 'Modelo F', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_8a1ea11b.jpg', tags: ['Casual'] },
  { id: 15, title: 'Modelo G', price: 399.00, img: 'assets/img/catalogo/Imagen de WhatsApp 2025-08-13 a las 23.17.21_d88b1449.jpg', tags: ['Casual'] },
  { id: 16, title: 'Modelo H', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.13.59_7b9dc4d2.jpg', tags: ['Casual'] },
  { id: 17, title: 'Modelo I', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.02_bf561da3.jpg', tags: ['Casual'] },
  { id: 18, title: 'Modelo J', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.03_228cb600.jpg', tags: ['Casual'] },
  { id: 19, title: 'Modelo K', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.03_2aa192ec.jpg', tags: ['Casual'] },
  { id: 20, title: 'Modelo L', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.03_6f231b01.jpg', tags: ['Casual'] },
  { id: 21, title: 'Modelo M', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.03_7d940ca6.jpg', tags: ['Casual'] },
  { id: 22, title: 'Modelo N', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.03_bb518f5f.jpg', tags: ['Casual'] },
  { id: 23, title: 'Modelo O', price: 399.00, img: 'assets/img/Imagen de WhatsApp 2025-09-08 a las 23.14.04_11ed4a09.jpg', tags: ['Casual'] },
];

const TESTIMONIALS = [
  { text: 'Excelente calidad y muy cómodos. El envío fue rápido.', name: 'María G.' },
  { text: 'Atención personalizada y variedad de modelos. Recomendado.', name: 'Carlos R.' },
  { text: 'Compré para mis hijos y quedaron encantados.', name: 'Lucía P.' },
];
