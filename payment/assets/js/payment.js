document.addEventListener('DOMContentLoaded', () => {
  const methodCards = document.querySelectorAll('.method-card');
  const detailsSections = document.querySelectorAll('.method-details');
  const paymentForm = document.getElementById('payment-form');
  const submitBtn = document.getElementById('submit-payment-btn');
  const selectedMethodInput = document.getElementById('selected-method');
  const appointmentId = document.getElementById('appointment-id').value;
  const csrfToken = document.getElementById('csrf-token').value;

  // Function to show toast
  const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    toast.className = `custom-toast shadow-lg`;
    toast.style.borderLeftColor = type === 'success' ? '#079d9c' : '#ef4444';
    
    const icon = type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
    
    toast.innerHTML = `
      <i class="bi ${icon} me-3 fs-4"></i>
      <div>
        <h6 class="mb-0 fw-bold">${type === 'success' ? 'Success' : 'Error'}</h6>
        <small class="text-secondary">${message}</small>
      </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
      toast.classList.add('show');
    }, 100);

    // Remove after 4 seconds
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => {
        toast.remove();
      }, 500);
    }, 4000);
  };

  // Selection logic for payment cards
  methodCards.forEach(card => {
    card.addEventListener('click', () => {
      const methodValue = card.getAttribute('data-method');
      
      // De-select all cards
      methodCards.forEach(c => c.classList.remove('selected'));
      // Hide all details
      detailsSections.forEach(s => s.style.display = 'none');

      // Select active card
      card.classList.add('selected');
      selectedMethodInput.value = methodValue;

      // Show matching details section if exists
      let targetId = 'details-';
      if (methodValue === 'UPI / QR') {
        targetId += 'upi-or-qr';
      } else {
        targetId += methodValue.replace(/\s+/g, '-').toLowerCase();
      }
      
      const targetDetail = document.getElementById(targetId);
      if (targetDetail) {
        targetDetail.style.display = 'block';
      }

      // Update button text contextually
      if (methodValue === 'PayPal') {
        submitBtn.innerHTML = '<i class="bi bi-paypal me-2"></i>Proceed to PayPal';
      } else if (methodValue === 'UPI / QR') {
        submitBtn.innerHTML = '<i class="bi bi-qr-code me-2"></i>Confirm UPI Payment';
      } else {
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirm Booking';
      }
    });
  });

  // Handle payment confirmation submission
  paymentForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const selectedMethod = selectedMethodInput.value;

    if (!selectedMethod) {
      showToast('Please select a payment method before proceeding.', 'error');
      return;
    }

    // If PayPal is selected, redirect to paypal.php (which handles post redirection)
    if (selectedMethod === 'PayPal') {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Redirecting to PayPal...';
      paymentForm.action = 'paypal.php';
      paymentForm.submit();
      return;
    }

    // For UPI / QR or Cash on Visit, process via AJAX
    try {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';

      const formData = new FormData();
      formData.append('appointment_id', appointmentId);
      formData.append('payment_method', selectedMethod);
      formData.append('csrf_token', csrfToken);

      const response = await fetch('ajax/process_payment.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => {
          window.location.href = 'payment_success.php?appointment_id=' + appointmentId;
        }, 2000);
      } else {
        showToast(result.message || 'An error occurred during payment processing.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = selectedMethod === 'UPI / QR' 
          ? '<i class="bi bi-qr-code me-2"></i>Confirm UPI Payment'
          : '<i class="bi bi-check-circle me-2"></i>Confirm Booking';
      }
    } catch (error) {
      console.error('Payment Error:', error);
      showToast('Failed to connect to the server. Please try again.', 'error');
      submitBtn.disabled = false;
      submitBtn.innerHTML = selectedMethod === 'UPI / QR' 
        ? '<i class="bi bi-qr-code me-2"></i>Confirm UPI Payment'
        : '<i class="bi bi-check-circle me-2"></i>Confirm Booking';
    }
  });
});
