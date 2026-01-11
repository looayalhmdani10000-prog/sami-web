// Simple JS: mobile nav toggle and form handling
document.addEventListener('DOMContentLoaded',function(){
  // Mobile nav toggle
  document.querySelectorAll('.nav-toggle').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const nav = btn.nextElementSibling || document.getElementById('siteNav')
      if(nav) nav.style.display = (nav.style.display==='flex' || nav.style.display==='block')? 'none' : 'block'
    })
  })

  // Contact form (contact.html)
  const contactForm = document.getElementById('contact-form')
  if(contactForm){
    contactForm.addEventListener('submit', function(e){
      e.preventDefault();
      document.getElementById('contactResult').textContent = 'Thanks! We will reply shortly.'
      contactForm.reset()
    })
  }

  // Booking form (booking.html) — no JS submit handler; form posts to server
  const bookingForm = document.getElementById('booking-form')
  if(bookingForm){
    // Intentionally no JS submit handler so the form submits normally to save_booking.php
  }
})