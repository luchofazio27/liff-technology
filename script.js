/* ==========================================================================
   LIFF TECHNOLOGY — Landing Page
   Funciones básicas del sitio
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* ------------------------------------------------------------------
     1. MENÚ MOBILE (hamburguesa)
  ------------------------------------------------------------------ */
  const menuToggle = document.getElementById('menuToggle');
  const navLinks = document.getElementById('navLinks');

  if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', function () {
      menuToggle.classList.toggle('open');
      navLinks.classList.toggle('open');
    });

    // cerrar el menú al hacer click en un link
    navLinks.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        menuToggle.classList.remove('open');
        navLinks.classList.remove('open');
      });
    });
  }

  /* ------------------------------------------------------------------
     2. HEADER: sombra al hacer scroll
  ------------------------------------------------------------------ */
  const header = document.querySelector('header');
  if (header) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 12) {
        header.style.boxShadow = '0 4px 14px rgba(16,29,51,0.06)';
      } else {
        header.style.boxShadow = 'none';
      }
    });
  }

  /* ------------------------------------------------------------------
     3. BOTÓN "VOLVER ARRIBA"
  ------------------------------------------------------------------ */
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 500) {
        backToTop.classList.add('show');
      } else {
        backToTop.classList.remove('show');
      }
    });
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ------------------------------------------------------------------
     4. AÑO AUTOMÁTICO EN EL FOOTER
  ------------------------------------------------------------------ */
  const yearEl = document.getElementById('currentYear');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  /* ------------------------------------------------------------------
     5. FORMULARIO DE CONTACTO
     Valida los campos y envía los datos a tu backend.
     Reemplazá FORM_ENDPOINT por la URL real una vez que la tengas
     configurada en Hostinger (PHP, Node, un servicio como Formspree,
     o cualquier endpoint que reciba JSON por POST).
  ------------------------------------------------------------------ */
  const FORM_ENDPOINT = 'contacto.php'; // ruta relativa: funciona igual en localhost y en Hostinger

  const form = document.getElementById('contactForm');
  const formFeedback = document.getElementById('formFeedback');
  const submitBtn = document.getElementById('formSubmit');

  function showFieldError(fieldWrapper, message) {
    fieldWrapper.classList.add('invalid');
    const errorEl = fieldWrapper.querySelector('.field-error');
    if (errorEl) errorEl.textContent = message;
  }

  function clearFieldError(fieldWrapper) {
    fieldWrapper.classList.remove('invalid');
  }

  function showFeedback(message, type) {
    if (!formFeedback) return;
    formFeedback.textContent = message;
    formFeedback.className = 'form-feedback show ' + type;
  }

  function validateForm(data) {
    let isValid = true;

    const nombreWrap = document.getElementById('nombreField');
    const emailWrap = document.getElementById('emailField');

    clearFieldError(nombreWrap);
    clearFieldError(emailWrap);

    if (!data.nombre || data.nombre.trim().length < 2) {
      showFieldError(nombreWrap, 'Ingresá tu nombre completo.');
      isValid = false;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!data.email || !emailPattern.test(data.email)) {
      showFieldError(emailWrap, 'Ingresá un email válido.');
      isValid = false;
    }

    return isValid;
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const data = {
        nombre: document.getElementById('nombre').value,
        email: document.getElementById('email').value,
        tipo: document.getElementById('tipo').value,
        mensaje: document.getElementById('mensaje').value,
      };

      if (!validateForm(data)) {
        showFeedback('Revisá los campos marcados antes de continuar.', 'error');
        return;
      }

      // Si todavía no configuraste un backend, solo mostramos confirmación visual.
      if (!FORM_ENDPOINT) {
        showFeedback('¡Gracias! Conectá FORM_ENDPOINT en script.js para recibir estos datos en tu backend/email.', 'success');
        form.reset();
        return;
      }

      // Envío real al backend una vez configurado FORM_ENDPOINT.
      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando...';

      fetch(FORM_ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
        .then(function (response) {
          return response.json().catch(function () { return {}; }).then(function (body) {
            return { status: response.status, body: body };
          });
        })
        .then(function (result) {
          if (result.body && result.body.ok) {
            showFeedback(result.body.message || '¡Gracias! Tu solicitud fue enviada, te contactamos a la brevedad.', 'success');
            form.reset();
          } else {
            const errorMsg = (result.body && result.body.error) || 'Hubo un problema al enviar el formulario. Probá nuevamente en unos minutos.';
            showFeedback(errorMsg, 'error');
          }
        })
        .catch(function () {
          showFeedback('No se pudo conectar con el servidor. Verificá tu conexión e intentá de nuevo.', 'error');
        })
        .finally(function () {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Enviar solicitud';
        });
    });
  }

  /* ------------------------------------------------------------------
     6. SCROLL SUAVE PARA LINKS INTERNOS (compatibilidad extra)
  ------------------------------------------------------------------ */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId.length > 1) {
        const target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

});
